<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver;

use Spiral\Database\Exception\CompilerException;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;
use Spiral\Database\Query\BuilderInterface;

/**
 * Responsible for conversion of set of query parameters (where tokens, table names and etc) into
 * sql to be send into specific Driver.
 */
abstract class Compiler implements CompilerInterface
{
    /**
     * Tokens for nested OR and AND conditions.
     */
    public const TOKEN_AND = '@AND';
    public const TOKEN_OR  = '@OR';

    /**
     * Query types for parameter ordering.
     */
    public const SELECT_QUERY = 'select';
    public const UPDATE_QUERY = 'update';
    public const DELETE_QUERY = 'delete';
    public const INSERT_QUERY = 'insert';

    /** @var Quoter */
    private $quoter = null;

    /**
     * QueryCompiler constructor.
     *
     * @param Quoter $quoter
     */
    public function __construct(Quoter $quoter)
    {
        $this->quoter = $quoter;
    }

    /**
     * Reset aliases cache.
     */
    public function __clone()
    {
        $this->quoter = clone $this->quoter;
    }

    /**
     * @inheritDoc
     */
    public function getPrefix(): string
    {
        return $this->quoter->getPrefix();
    }

    /**
     * @inheritDoc
     */
    public function quote(QueryBindings $bindings, $identifier, bool $isTable = false): string
    {
        if ($identifier instanceof FragmentInterface) {
            return $this->compileFragment($bindings, $identifier);
        }

        if ($identifier instanceof ParameterInterface) {
            return $this->compileValue($bindings, $identifier);
        }

        return $this->quoter->quote($identifier, $isTable);
    }

    /**
     * @inheritDoc
     */
    public function compileInsert(
        QueryBindings $bindings,
        string $table,
        array $columns,
        array $values
    ): string {
        if ($columns === []) {
            throw new CompilerException(
                'Unable to build insert statement, columns must be set'
            );
        }

        if ($values === []) {
            throw new CompilerException(
                'Unable to build insert statement, at least one value set must be provided'
            );
        }

        $rowsets = [];
        foreach ($values as $rowset) {
            $rowsets[] = $this->compileValue($bindings, $rowset);
        }

        return sprintf(
            "INSERT INTO %s (%s)\nVALUES %s",
            $this->quote($bindings, $table, true),
            $this->compileColumns($bindings, $columns),
            join(', ', $rowsets)
        );
    }

    /**
     * @inheritDoc
     */
    public function compileUpdate(
        QueryBindings $bindings,
        string $table,
        array $updates,
        array $whereTokens = []
    ): string {
        return sprintf(
            "UPDATE %s\nSET %s%s",
            $this->quote($bindings, $table, true),
            $this->compileSetColumns($bindings, $updates),
            $this->optional("\nWHERE", $this->compileWhere($bindings, $whereTokens))
        );
    }

    /**
     * @inheritDoc
     */
    public function compileDelete(
        QueryBindings $bindings,
        string $table,
        array $whereTokens = []
    ): string {
        return sprintf(
            'DELETE FROM %s%s',
            $this->quote($bindings, $table, true),
            $this->optional("\nWHERE", $this->compileWhere($bindings, $whereTokens))
        );
    }

    /**
     * @inheritDoc
     */
    public function compileSelect(
        QueryBindings $bindings,
        array $fromTables,
        $distinct,
        array $columns,
        array $joinTokens = [],
        array $whereTokens = [],
        array $havingTokens = [],
        array $grouping = [],
        array $orderBy = [],
        int $limit = 0,
        int $offset = 0,
        array $unionTokens = [],
        bool $forUpdate = false
    ): string {
        // This statement(s) parts should be processed first to define set of table and column aliases
        $tableNames = $this->compileTables($bindings, $fromTables);
        $joinsStatement = $this->compileJoins($bindings, $joinTokens);

        return sprintf(
            "SELECT%s\n%s\nFROM %s%s%s%s%s%s%s%s%s",
            $this->optional(' ', $this->compileDistinct($bindings, $distinct)),
            $this->compileColumns($bindings, $columns),
            $tableNames,
            $this->optional(' ', $joinsStatement, ' '),
            $this->optional("\nWHERE", $this->compileWhere($bindings, $whereTokens)),
            $this->optional("\nGROUP BY", $this->compileGroupBy($bindings, $grouping), ' '),
            $this->optional("\nHAVING", $this->compileWhere($bindings, $havingTokens)),
            $this->optional("\n", $this->compileUnions($bindings, $unionTokens)),
            $this->optional("\nORDER BY", $this->compileOrderBy($bindings, $orderBy)),
            $this->optional("\n", $this->compileLimit($bindings, $limit, $offset)),
            $this->optional(' ', $forUpdate ? 'FOR UPDATE' : '')
        );
    }

    /**
     * Quote and wrap column identifiers (used in insert statement compilation).
     *
     * @param QueryBindings $bindings
     * @param array         $columns
     * @param int           $maxLength Automatically wrap columns.
     * @return string
     */
    protected function compileColumns(
        QueryBindings $bindings,
        array $columns,
        int $maxLength = 180
    ): string {
        //Let's quote every identifier
        $columns = array_map(function ($column) use ($bindings) {
            return $this->quote($bindings, $column);
        }, $columns);

        return wordwrap(implode(', ', $columns), $maxLength);
    }

    /**
     * Prepare column values to be used in UPDATE statement.
     *
     * @param QueryBindings $bindings
     * @param array         $updates
     * @return string
     */
    protected function compileSetColumns(QueryBindings $bindings, array $updates): string
    {
        foreach ($updates as $column => &$value) {
            $value = "{$this->quote($bindings, $column)} = {$this->compileValue($bindings, $value)}";
            unset($value);
        }

        return trim(implode(', ', $updates));
    }

    /**
     * Compile DISTINCT statement.
     *
     * @param QueryBindings $bindings
     * @param mixed         $distinct Not every DBMS support distinct expression, only Postgres does.
     * @return string
     */
    protected function compileDistinct(QueryBindings $bindings, $distinct): string
    {
        if (empty($distinct)) {
            return '';
        }

        return 'DISTINCT';
    }

    /**
     * Compile table names statement.
     *
     * @param QueryBindings $bindings
     * @param array         $tables
     * @return string
     */
    protected function compileTables(QueryBindings $bindings, array $tables): string
    {
        foreach ($tables as &$table) {
            $table = $this->quote($bindings, $table, true);
            unset($table);
        }

        return implode(', ', $tables);
    }

    /**
     * Compiler joins statement.
     *
     * @param QueryBindings $bindings
     * @param array         $joinTokens
     * @return string
     */
    protected function compileJoins(QueryBindings $bindings, array $joinTokens): string
    {
        $statement = '';
        foreach ($joinTokens as $join) {
            $statement .= "\n{$join['type']} JOIN {$this->quote($bindings, $join['outer'], true)}";

            if (!empty($join['alias'])) {
                $this->quoter->registerAlias($join['alias'], (string)$join['outer']);
                $statement .= ' AS ' . $this->quote($bindings, $join['alias']);
            }

            $statement .= $this->optional("\n    ON", $this->compileWhere($bindings, $join['on']));
        }

        return $statement;
    }

    /**
     * Compile union statement chunk. Keywords UNION and ALL will be included, this methods will
     * automatically move every union on new line.
     *
     * @param QueryBindings $bindings
     * @param array         $unionTokens
     * @return string
     */
    protected function compileUnions(QueryBindings $bindings, array $unionTokens): string
    {
        if ($unionTokens === []) {
            return '';
        }

        $statement = '';
        foreach ($unionTokens as $union) {
            $select = $this->compileFragment($bindings, $union[1]);

            if ($union[0] !== '') {
                //First key is union type, second united query (no need to share compiler)
                $statement .= "\nUNION {$union[0]}\n{$select}";
            } else {
                //No extra space
                $statement .= "\nUNION \n{$select}";
            }
        }

        return ltrim($statement, "\n");
    }

    /**
     * Compile ORDER BY statement.
     *
     * @param QueryBindings $bindings
     * @param array         $ordering
     * @return string
     */
    protected function compileOrderBy(QueryBindings $bindings, array $ordering): string
    {
        $result = [];
        foreach ($ordering as $order) {
            $direction = strtoupper($order[1]);

            if (!in_array($direction, ['ASC', 'DESC'])) {
                throw new CompilerException('Invalid sorting direction, only ASC and DESC are allowed');
            }

            $result[] = $this->quote($bindings, $order[0]) . ' ' . $direction;
        }

        return implode(', ', $result);
    }

    /**
     * Compiler GROUP BY statement.
     *
     * @param QueryBindings $bindings
     * @param array         $grouping
     * @return string
     */
    protected function compileGroupBy(QueryBindings $bindings, array $grouping): string
    {
        $statement = '';
        foreach ($grouping as $identifier) {
            $statement .= $this->quote($bindings, $identifier);
        }

        return $statement;
    }

    /**
     * Compile limit statement.
     *
     * @param QueryBindings $bindings
     * @param int           $limit
     * @param int           $offset
     * @return string
     */
    protected function compileLimit(QueryBindings $bindings, int $limit, int $offset): string
    {
        if (empty($limit) && empty($offset)) {
            return '';
        }

        $statement = '';
        if (!empty($limit)) {
            $statement = "LIMIT {$limit} ";
        }

        if (!empty($offset)) {
            $statement .= "OFFSET {$offset}";
        }

        return trim($statement);
    }

    /**
     * Compile where statement.
     *
     * @param QueryBindings $bindings
     * @param array         $tokens
     * @return string
     *
     * @throws CompilerException
     */
    protected function compileWhere(QueryBindings $bindings, array $tokens): string
    {
        if ($tokens === []) {
            return '';
        }

        $statement = '';

        $activeGroup = true;
        foreach ($tokens as $condition) {
            // OR/AND keyword
            $boolean = $condition[0];
            $context = $condition[1];

            //First condition in group/query, no any AND, OR required
            if ($activeGroup) {
                //Kill AND, OR and etc.
                $boolean = '';

                //Next conditions require AND or OR
                $activeGroup = false;
            }

            /*
             * When context is string it usually represent control keyword/syntax such as opening
             * or closing braces.
             */
            if (is_string($context)) {
                if ($context == '(') {
                    //New where group.
                    $activeGroup = true;
                }

                $statement .= ltrim("{$boolean} {$context} ");

                if ($context == ')') {
                    //We don't need trailing space
                    $statement = rtrim($statement);
                }

                continue;
            }

            if ($context instanceof FragmentInterface) {
                //Fragments has to be compiled separately
                $statement .= "{$boolean} {$this->compileFragment($bindings, $context)} ";
                continue;
            }

            //Now we are operating with "class" where function, where we need 3 variables where(id, =, 1)
            if (!is_array($context)) {
                throw new CompilerException('Invalid where token, context expected to be an array');
            }

            /*
             * This is "normal" where token which includes identifier, operator and value.
             */
            list($identifier, $operator, $value) = $context;

            //Identifier can be column name, expression or even query builder
            $identifier = $this->quote($bindings, $identifier);

            //Value has to be prepared as well
            $placeholder = $this->compileValue($bindings, $value, true);

            if ($operator == 'BETWEEN' || $operator == 'NOT BETWEEN') {
                //Between statement has additional parameter
                $right = $this->compileValue($bindings, $context[3], true);

                $statement .= "{$boolean} {$identifier} {$operator} {$placeholder} AND {$right} ";
                continue;
            }

            //Compiler can switch equal to IN if value points to array (do we need it?)
            $operator = $this->compileOperator($bindings, $value, $operator);

            $statement .= "{$boolean} {$identifier} {$operator} {$placeholder} ";
        }

        if ($activeGroup) {
            throw new CompilerException('Unable to build where statement, unclosed where group');
        }

        if (trim($statement, ' ()') === '') {
            return '';
        }

        return trim($statement);
    }

    /**
     * Resolve operator value based on value value. ;).
     *
     * @param QueryBindings $bindings
     * @param mixed         $parameter
     * @param string|mixed  $operator
     * @return string
     */
    protected function compileOperator(QueryBindings $bindings, $parameter, $operator): string
    {
        if ($operator instanceof FragmentInterface) {
            return $this->compileFragment($bindings, $operator);
        }

        if (!$parameter instanceof ParameterInterface) {
            return $operator;
        }

        if ($parameter->getType() == \PDO::PARAM_NULL) {
            switch ($operator) {
                case '=':
                    return 'IS';
                case '!=':
                    return 'IS NOT';
            }
        }

        if ($operator != '=' || is_scalar($parameter->getValue())) {
            // doing nothing for non equal operators
            return $operator;
        }

        if ($parameter->isArray()) {
            // automatically switching between equal and IN
            return 'IN';
        }

        return $operator;
    }

    /**
     * Prepare value to be replaced into query (replace ?).
     *
     * @param QueryBindings $bindings
     * @param mixed         $value
     * @param bool          $compileNull Do not register null parameters in binding but rather replace them with NULL.
     * @return string
     */
    protected function compileValue(QueryBindings $bindings, $value, bool $compileNull = false): string
    {
        if ($value instanceof FragmentInterface) {
            return $this->compileFragment($bindings, $value);
        }

        if (!$value instanceof ParameterInterface) {
            $value = new Parameter($value);
        }

        if ($value->isArray()) {
            $values = [];
            foreach ($value->getValue() as $child) {
                $values[] = $this->compileValue($bindings, $child, $compileNull);
            }

            return '(' . join(', ', $values) . ')';
        }

        if ($compileNull && $value->getValue() === null) {
            return 'NULL';
        }

        $bindings->push($value);

        return '?';
    }

    /**
     * Prepare where fragment to be injected into statement.
     *
     * @param QueryBindings     $bindings
     * @param FragmentInterface $context
     * @return string
     */
    protected function compileFragment(QueryBindings $bindings, FragmentInterface $context): string
    {
        $compiler = $this;
        if ($context instanceof BuilderInterface) {
            if ($context->getCompiler() !== null) {
                // keep the aliases map
                $compiler = clone $this;
                $compiler->quoter = $this->quoter->withPrefix($context->getCompiler()->getPrefix(), true);
            }

            return '(' . $context->compile($bindings, $compiler) . ')';
        }

        //Fragments does not need braces around them
        return $context->compile($bindings, $compiler);
    }

    /**
     * Combine expression with prefix/postfix (usually SQL keyword) but only if expression is not
     * empty.
     *
     * @param string $prefix
     * @param string $expression
     * @param string $postfix
     * @return string
     */
    protected function optional(string $prefix, string $expression, string $postfix = ''): string
    {
        if (empty($expression)) {
            return '';
        }

        if ($prefix != "\n" && $prefix != ' ') {
            $prefix .= ' ';
        }

        return $prefix . $expression . $postfix;
    }
}
