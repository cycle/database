<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Entity;

use Spiral\Database\Builder\QueryBuilder;
use Spiral\Database\Exception\CompilerException;
use Spiral\Database\Injection\ExpressionInterface;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Injection\ParameterInterface;

/**
 * Responsible for conversion of set of query parameters (where tokens, table names and etc) into
 * sql to be send into specific Driver.
 *
 * Source of Compiler must be optimized in nearest future.
 */
class QueryCompiler
{
    /**
     * Query types for parameter ordering.
     */
    const SELECT_QUERY = 'select';
    const UPDATE_QUERY = 'update';
    const DELETE_QUERY = 'delete';
    const INSERT_QUERY = 'insert';

    /**
     * Quotes names and expressions.
     *
     * @var Quoter
     */
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
     * Prefix associated with compiler.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->quoter->getPrefix();
    }

    /**
     * Reset table aliases cache, required if same compiler used twice.
     *
     * @return self
     */
    public function resetQuoter(): QueryCompiler
    {
        $this->quoter->reset();

        return $this;
    }

    /**
     * Query query identifier, if identified stated as table - table prefix must be added.
     *
     * @param string|FragmentInterface $identifier Identifier can include simple column operations
     *                                             and functions, having "." in it will
     *                                             automatically force table prefix to first value.
     * @param bool                     $isTable    Set to true to let quote method know that
     *                                             identified is related to table name.
     *
     * @return string
     */
    public function quote($identifier, bool $isTable = false): string
    {
        if ($identifier instanceof FragmentInterface) {
            return $this->prepareFragment($identifier);
        }

        return $this->quoter->quote($identifier, $isTable);
    }

    /**
     * Create insert query using table names, columns and rowsets. Must support both - single and
     * batch inserts.
     *
     * @param string              $table
     * @param array               $columns
     * @param FragmentInterface[] $rowsets Every rowset has to be convertable into string. Raw data
     *                                     not allowed!
     *
     * @return string
     *
     * @throws CompilerException
     */
    public function compileInsert(string $table, array $columns, array $rowsets): string
    {
        if (empty($columns)) {
            throw new CompilerException(
                'Unable to build insert statement, columns must be set'
            );
        }

        if (empty($rowsets)) {
            throw new CompilerException(
                'Unable to build insert statement, at least one value set must be provided'
            );
        }

        //To add needed prefixes (if any)
        $table = $this->quote($table, true);

        //Compiling list of columns
        $columns = $this->prepareColumns($columns);

        //Simply joining every rowset
        $rowsets = implode(",\n", $rowsets);

        return "INSERT INTO {$table} ({$columns})\nVALUES {$rowsets}";
    }

    /**
     * Create update statement.
     *
     * @param string $table
     * @param array  $updates
     * @param array  $whereTokens
     *
     * @return string
     *
     * @throws CompilerException
     */
    public function compileUpdate(string $table, array $updates, array $whereTokens = []): string
    {
        $table = $this->quote($table, true);

        //Preparing update column statement
        $updates = $this->prepareUpdates($updates);

        //Where statement is optional for update queries
        $whereStatement = $this->optional("\nWHERE", $this->compileWhere($whereTokens));

        return rtrim("UPDATE {$table}\nSET {$updates} {$whereStatement}");
    }

    /**
     * Create delete statement.
     *
     * @param string $table
     * @param array  $whereTokens
     *
     * @return string
     *
     * @throws CompilerException
     */
    public function compileDelete(string $table, array $whereTokens = []): string
    {
        $table = $this->quote($table, true);

        //Where statement is optional for delete query (which is weird)
        $whereStatement = $this->optional("\nWHERE", $this->compileWhere($whereTokens));

        return rtrim("DELETE FROM {$table} {$whereStatement}");
    }

    /**
     * Create select statement. Compiler must validly resolve table and column aliases used in
     * conditions and joins.
     *
     * @param array       $fromTables
     * @param bool|string $distinct String only for PostgresSQL.
     * @param array       $columns
     * @param array       $joinTokens
     * @param array       $whereTokens
     * @param array       $havingTokens
     * @param array       $grouping
     * @param array       $ordering
     * @param int         $limit
     * @param int         $offset
     * @param array       $unionTokens
     *
     * @return string
     *
     * @throws CompilerException
     */
    public function compileSelect(
        array $fromTables,
        $distinct,
        array $columns,
        array $joinTokens = [],
        array $whereTokens = [],
        array $havingTokens = [],
        array $grouping = [],
        array $ordering = [],
        int $limit = 0,
        int $offset = 0,
        array $unionTokens = []
    ): string {
        //This statement parts should be processed first to define set of table and column aliases
        $fromTables = $this->compileTables($fromTables);

        $joinsStatement = $this->optional(' ', $this->compileJoins($joinTokens), ' ');

        //Distinct flag (if any)
        $distinct = $this->optional(' ', $this->compileDistinct($distinct));

        //Columns are compiled after table names and joins to ensure aliases and prefixes
        $columns = $this->prepareColumns($columns);

        //A lot of constrain and other statements
        $whereStatement = $this->optional("\nWHERE", $this->compileWhere($whereTokens));
        $havingStatement = $this->optional("\nHAVING", $this->compileWhere($havingTokens));
        $groupingStatement = $this->optional("\nGROUP BY", $this->compileGrouping($grouping), ' ');

        //Union statement has new line at beginning of every union
        $unionsStatement = $this->optional("\n", $this->compileUnions($unionTokens));
        $orderingStatement = $this->optional("\nORDER BY", $this->compileOrdering($ordering));

        $limingStatement = $this->optional("\n", $this->compileLimit($limit, $offset));

        //Initial statement have predictable order
        $statement = "SELECT{$distinct}\n{$columns}\nFROM {$fromTables}";
        $statement .= "{$joinsStatement}{$whereStatement}{$groupingStatement}{$havingStatement}";
        $statement .= "{$unionsStatement}{$orderingStatement}{$limingStatement}";

        return rtrim($statement);
    }

    /**
     * Quote and wrap column identifiers (used in insert statement compilation).
     *
     * @param array $columnIdentifiers
     * @param int   $maxLength Automatically wrap columns.
     *
     * @return string
     */
    protected function prepareColumns(array $columnIdentifiers, int $maxLength = 180): string
    {
        //Let's quote every identifier
        $columnIdentifiers = array_map([$this, 'quote'], $columnIdentifiers);

        return wordwrap(implode(', ', $columnIdentifiers), $maxLength);
    }

    /**
     * Prepare column values to be used in UPDATE statement.
     *
     * @param array $updates
     *
     * @return string
     */
    protected function prepareUpdates(array $updates): string
    {
        foreach ($updates as $column => &$value) {
            if ($value instanceof FragmentInterface) {
                $value = $this->prepareFragment($value);
            } else {
                //Simple value (such condition should never be met since every value has to be
                //wrapped using parameter interface)
                $value = '?';
            }

            $value = "{$this->quote($column)} = {$value}";
            unset($value);
        }

        return trim(implode(', ', $updates));
    }

    /**
     * Compile DISTINCT statement.
     *
     * @param mixed $distinct Not every DBMS support distinct expression, only Postgres does.
     *
     * @return string
     */
    protected function compileDistinct($distinct): string
    {
        if (empty($distinct)) {
            return '';
        }

        return 'DISTINCT';
    }

    /**
     * Compile table names statement.
     *
     * @param array $tables
     *
     * @return string
     */
    protected function compileTables(array $tables): string
    {
        foreach ($tables as &$table) {
            $table = $this->quote($table, true);
            unset($table);
        }

        return implode(', ', $tables);
    }

    /**
     * Compiler joins statement.
     *
     * @param array $joinTokens
     *
     * @return string
     */
    protected function compileJoins(array $joinTokens): string
    {
        $statement = '';
        foreach ($joinTokens as $join) {
            $statement .= "\n{$join['type']} JOIN {$this->quote($join['outer'], true)}";

            if (!empty($join['alias'])) {
                $this->quoter->registerAlias($join['alias'], (string)$join['outer']);
                $statement .= " AS " . $this->quote($join['alias']);
            }

            $statement .= $this->optional("\n    ON", $this->compileWhere($join['on']));
        }

        return $statement;
    }

    /**
     * Compile union statement chunk. Keywords UNION and ALL will be included, this methods will
     * automatically move every union on new line.
     *
     * @param array $unionTokens
     *
     * @return string
     */
    protected function compileUnions(array $unionTokens): string
    {
        if (empty($unionTokens)) {
            return '';
        }

        $statement = '';
        foreach ($unionTokens as $union) {
            if (!empty($union[0])) {
                //First key is union type, second united query (no need to share compiler)
                $statement .= "\nUNION {$union[0]}\n({$union[1]})";
            } else {
                //No extra space
                $statement .= "\nUNION \n({$union[1]})";
            }
        }

        return ltrim($statement, "\n");
    }

    /**
     * Compile ORDER BY statement.
     *
     * @param array $ordering
     *
     * @return string
     */
    protected function compileOrdering(array $ordering): string
    {
        $result = [];
        foreach ($ordering as $order) {
            $direction = strtoupper($order[1]);

            if (!in_array($direction, ['ASC', 'DESC'])) {
                throw new CompilerException("Invalid sorting direction, only ASC and DESC are allowed");
            }

            $result[] = $this->quote($order[0]) . ' ' . $direction;
        }

        return implode(', ', $result);
    }

    /**
     * Compiler GROUP BY statement.
     *
     * @param array $grouping
     *
     * @return string
     */
    protected function compileGrouping(array $grouping): string
    {
        $statement = '';
        foreach ($grouping as $identifier) {
            $statement .= $this->quote($identifier);
        }

        return $statement;
    }

    /**
     * Compile limit statement.
     *
     * @param int $limit
     * @param int $offset
     *
     * @return string
     */
    protected function compileLimit(int $limit, int $offset): string
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
     * @param array $tokens
     *
     * @return string
     *
     * @throws CompilerException
     */
    protected function compileWhere(array $tokens): string
    {
        if (empty($tokens)) {
            return '';
        }

        $statement = '';

        $activeGroup = true;
        foreach ($tokens as $condition) {
            //OR/AND keyword
            $boolean = $condition[0];

            //See AbstractWhere
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
                $statement .= "{$boolean} {$this->prepareFragment($context)} ";
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
            $identifier = $this->quote($identifier);

            //Value has to be prepared as well
            $placeholder = $this->prepareValue($value);

            if ($operator == 'BETWEEN' || $operator == 'NOT BETWEEN') {
                //Between statement has additional parameter
                $right = $this->prepareValue($context[3]);

                $statement .= "{$boolean} {$identifier} {$operator} {$placeholder} AND {$right} ";
                continue;
            }

            //Compiler can switch equal to IN if value points to array (do we need it?)
            $operator = $this->prepareOperator($value, $operator);

            $statement .= "{$boolean} {$identifier} {$operator} {$placeholder} ";
        }

        if ($activeGroup) {
            throw new CompilerException('Unable to build where statement, unclosed where group');
        }

        return trim($statement);
    }

    /**
     * Combine expression with prefix/postfix (usually SQL keyword) but only if expression is not
     * empty.
     *
     * @param string $prefix
     * @param string $expression
     * @param string $postfix
     *
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

    /**
     * Resolve operator value based on value value. ;).
     *
     * @param mixed  $parameter
     * @param string $operator
     *
     * @return string
     */
    protected function prepareOperator($parameter, string $operator): string
    {
        if (!$parameter instanceof ParameterInterface) {
            //Probably fragment
            return $operator;
        }

        if ($operator != '=' || is_scalar($parameter->getValue())) {
            //Doing nothing for non equal operators
            return $operator;
        }

        if ($parameter->isArray()) {
            //Automatically switching between equal and IN
            return 'IN';
        }

        return $operator;
    }

    /**
     * Prepare value to be replaced into query (replace ?).
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function prepareValue($value): string
    {
        if ($value instanceof FragmentInterface) {
            return $this->prepareFragment($value);
        }

        //Technically should never happen (but i prefer to keep this legacy code)
        return '?';
    }

    /**
     * Prepare where fragment to be injected into statement.
     *
     * @param FragmentInterface $context
     *
     * @return string
     */
    protected function prepareFragment(FragmentInterface $context): string
    {
        if ($context instanceof QueryBuilder) {
            //Nested queries has to be wrapped with braces
            return '(' . $context->sqlStatement($this) . ')';
        }

        if ($context instanceof ExpressionInterface) {
            //Fragments does not need braces around them
            return $context->sqlStatement($this);
        }

        return $context->sqlStatement();
    }
}
