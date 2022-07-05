<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use Cycle\Database\Exception\CompilerException;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Injection\ParameterInterface;
use Cycle\Database\Query\QueryParameters;
use Spiral\Database\Query\QueryParameters as SpiralQueryParameters;
use Spiral\Database\Injection\FragmentInterface as SpiralFragmentInterface;
use Spiral\Database\Driver\Quoter as SpiralQuoter;
use Spiral\Database\Driver\Compiler as SpiralCompiler;

interface_exists(SpiralFragmentInterface::class);
class_exists(SpiralQueryParameters::class);
class_exists(SpiralQuoter::class);

abstract class Compiler implements CompilerInterface
{
    /** @var Quoter */
    private $quoter;

    /**
     * Compiler constructor.
     *
     * @param string $quotes
     */
    public function __construct(string $quotes = '""')
    {
        $this->quoter = new Quoter('', $quotes);
    }

    /**
     * @param string $identifier
     *
     * @return string
     */
    public function quoteIdentifier(string $identifier): string
    {
        return $this->quoter->identifier($identifier);
    }

    /**
     * @param QueryParameters   $params
     * @param string            $prefix
     * @param FragmentInterface $fragment
     *
     * @return string
     */
    public function compile(
        SpiralQueryParameters $params,
        string $prefix,
        SpiralFragmentInterface $fragment
    ): string {
        return $this->fragment(
            $params,
            $this->quoter->withPrefix($prefix),
            $fragment,
            false
        );
    }

    /**
     * @inheritDoc
     */
    public function hashLimit(SpiralQueryParameters $params, array $tokens): string
    {
        if ($tokens['limit'] !== null) {
            $params->push(new Parameter($tokens['limit']));
        }

        if ($tokens['offset'] !== null) {
            $params->push(new Parameter($tokens['offset']));
        }

        return '_' . ($tokens['limit'] === null) . '_' . ($tokens['offset'] === null);
    }

    /**
     * @param QueryParameters   $params
     * @param Quoter            $q
     * @param FragmentInterface $fragment
     * @param bool              $nestedQuery
     *
     * @return string
     */
    protected function fragment(
        SpiralQueryParameters $params,
        SpiralQuoter $q,
        SpiralFragmentInterface $fragment,
        bool $nestedQuery = true
    ): string {
        $tokens = $fragment->getTokens();

        switch ($fragment->getType()) {
            case self::FRAGMENT:
                foreach ($tokens['parameters'] as $param) {
                    $params->push($param);
                }

                return $tokens['fragment'];

            case self::EXPRESSION:
                foreach ($tokens['parameters'] as $param) {
                    $params->push($param);
                }

                return $q->quote($tokens['expression']);

            case self::INSERT_QUERY:
                return $this->insertQuery($params, $q, $tokens);

            case self::SELECT_QUERY:
                if ($nestedQuery) {
                    if ($fragment->getPrefix() !== null) {
                        $q = $q->withPrefix(
                            $fragment->getPrefix(),
                            true
                        );
                    }

                    return sprintf(
                        '(%s)',
                        $this->selectQuery($params, $q, $tokens)
                    );
                }

                return $this->selectQuery($params, $q, $tokens);

            case self::UPDATE_QUERY:
                return $this->updateQuery($params, $q, $tokens);

            case self::DELETE_QUERY:
                return $this->deleteQuery($params, $q, $tokens);
        }

        throw new CompilerException(
            sprintf(
                'Unknown fragment type %s',
                $fragment->getType()
            )
        );
    }

    /**
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param array           $tokens
     *
     * @return string
     */
    protected function insertQuery(SpiralQueryParameters $params, SpiralQuoter $q, array $tokens): string
    {
        $values = [];
        foreach ($tokens['values'] as $value) {
            $values[] = $this->value($params, $q, $value);
        }

        if ($tokens['columns'] === []) {
            return sprintf(
                'INSERT INTO %s DEFAULT VALUES',
                $this->name($params, $q, $tokens['table'], true)
            );
        }

        return sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->name($params, $q, $tokens['table'], true),
            $this->columns($params, $q, $tokens['columns']),
            implode(', ', $values)
        );
    }

    /**
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param array           $tokens
     *
     * @return string
     */
    protected function selectQuery(SpiralQueryParameters $params, SpiralQuoter $q, array $tokens): string
    {
        // This statement(s) parts should be processed first to define set of table and column aliases
        $tables = [];
        foreach ($tokens['from'] as $table) {
            $tables[] = $this->name($params, $q, $table, true);
        }

        $joins = $this->joins($params, $q, $tokens['join']);

        return sprintf(
            "SELECT%s %s\nFROM %s%s%s%s%s%s%s%s%s",
            $this->optional(' ', $this->distinct($params, $q, $tokens['distinct'])),
            $this->columns($params, $q, $tokens['columns']),
            implode(', ', $tables),
            $this->optional(' ', $joins, ' '),
            $this->optional("\nWHERE", $this->where($params, $q, $tokens['where'])),
            $this->optional("\nGROUP BY", $this->groupBy($params, $q, $tokens['groupBy']), ' '),
            $this->optional("\nHAVING", $this->where($params, $q, $tokens['having'])),
            $this->optional("\n", $this->unions($params, $q, $tokens['union'])),
            $this->optional("\nORDER BY", $this->orderBy($params, $q, $tokens['orderBy'])),
            $this->optional("\n", $this->limit($params, $q, $tokens['limit'], $tokens['offset'])),
            $this->optional(' ', $tokens['forUpdate'] ? 'FOR UPDATE' : '')
        );
    }

    /**
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param bool|string     $distinct
     *
     * @return string
     */
    protected function distinct(SpiralQueryParameters $params, SpiralQuoter $q, $distinct): string
    {
        if ($distinct === false) {
            return '';
        }

        return 'DISTINCT';
    }

    /**
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param array           $joins
     *
     * @return string
     */
    protected function joins(SpiralQueryParameters $params, SpiralQuoter $q, array $joins): string
    {
        $statement = '';
        foreach ($joins as $join) {
            $statement .= sprintf(
                "\n%s JOIN %s",
                $join['type'],
                $this->name($params, $q, $join['outer'], true)
            );

            if ($join['alias'] !== null) {
                $q->registerAlias($join['alias'], (string)$join['outer']);

                $statement .= ' AS ' . $this->name($params, $q, $join['alias']);
            }

            $statement .= $this->optional(
                "\n    ON",
                $this->where($params, $q, $join['on'])
            );
        }

        return $statement;
    }

    /**
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param array           $unions
     *
     * @return string
     */
    protected function unions(SpiralQueryParameters $params, SpiralQuoter $q, array $unions): string
    {
        if ($unions === []) {
            return '';
        }

        $statement = '';
        foreach ($unions as $union) {
            $select = $this->fragment($params, $q, $union[1]);

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
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param array           $orderBy
     *
     * @return string
     */
    protected function orderBy(SpiralQueryParameters $params, SpiralQuoter $q, array $orderBy): string
    {
        $result = [];
        foreach ($orderBy as $order) {
            $direction = strtoupper($order[1]);

            if (!in_array($direction, ['ASC', 'DESC'])) {
                throw new CompilerException(
                    'Invalid sorting direction, only ASC and DESC are allowed'
                );
            }

            $result[] = $this->name($params, $q, $order[0]) . ' ' . $direction;
        }

        return implode(', ', $result);
    }

    /**
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param array           $groupBy
     *
     * @return string
     */
    protected function groupBy(SpiralQueryParameters $params, SpiralQuoter $q, array $groupBy): string
    {
        $result = [];
        foreach ($groupBy as $identifier) {
            $result[] = $this->name($params, $q, $identifier);
        }

        return implode(', ', $result);
    }

    /**
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param int|null        $limit
     * @param int|null        $offset
     *
     * @return string
     */
    abstract protected function limit(
        SpiralQueryParameters $params,
        SpiralQuoter $q,
        int $limit = null,
        int $offset = null
    ): string;

    /**
     * @param QueryParameters $parameters
     * @param Quoter          $quoter
     * @param array           $tokens
     *
     * @return string
     */
    protected function updateQuery(
        SpiralQueryParameters $parameters,
        SpiralQuoter $quoter,
        array $tokens
    ): string {
        $values = [];
        foreach ($tokens['values'] as $column => $value) {
            $values[] = sprintf(
                '%s = %s',
                $this->name($parameters, $quoter, $column),
                $this->value($parameters, $quoter, $value)
            );
        }

        return sprintf(
            "UPDATE %s\nSET %s%s",
            $this->name($parameters, $quoter, $tokens['table'], true),
            trim(implode(', ', $values)),
            $this->optional("\nWHERE", $this->where($parameters, $quoter, $tokens['where']))
        );
    }

    /**
     * @param QueryParameters $parameters
     * @param Quoter          $quoter
     * @param array           $tokens
     *
     * @return string
     */
    protected function deleteQuery(
        SpiralQueryParameters $parameters,
        SpiralQuoter $quoter,
        array $tokens
    ): string {
        return sprintf(
            'DELETE FROM %s%s',
            $this->name($parameters, $quoter, $tokens['table'], true),
            $this->optional(
                "\nWHERE",
                $this->where($parameters, $quoter, $tokens['where'])
            )
        );
    }

    /**
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param mixed           $name
     * @param bool            $table
     *
     * @return string
     */
    protected function name(SpiralQueryParameters $params, SpiralQuoter $q, $name, bool $table = false): string
    {
        if ($name instanceof FragmentInterface) {
            return $this->fragment($params, $q, $name);
        }

        if ($name instanceof ParameterInterface) {
            return $this->value($params, $q, $name);
        }

        return $q->quote($name, $table);
    }

    /**
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param array           $columns
     * @param int             $maxLength
     *
     * @return string
     */
    protected function columns(SpiralQueryParameters $params, SpiralQuoter $q, array $columns, int $maxLength = 180): string
    {
        // let's quote every identifier
        $columns = array_map(
            function ($column) use ($params, $q) {
                return $this->name($params, $q, $column);
            },
            $columns
        );

        return wordwrap(implode(', ', $columns), $maxLength);
    }

    /**
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param mixed           $value
     *
     * @return string
     */
    protected function value(SpiralQueryParameters $params, SpiralQuoter $q, $value): string
    {
        if ($value instanceof FragmentInterface) {
            return $this->fragment($params, $q, $value);
        }

        if (!$value instanceof ParameterInterface) {
            $value = new Parameter($value);
        }

        if ($value->isArray()) {
            $values = [];
            foreach ($value->getValue() as $child) {
                $values[] = $this->value($params, $q, $child);
            }

            return '(' . implode(', ', $values) . ')';
        }

        $params->push($value);

        return '?';
    }

    /**
     * Compile where statement.
     *
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param array           $tokens
     *
     * @return string
     */
    protected function where(SpiralQueryParameters $params, SpiralQuoter $q, array $tokens): string
    {
        if ($tokens === []) {
            return '';
        }

        $statement = '';

        $activeGroup = true;
        foreach ($tokens as $condition) {
            // OR/AND keyword
            [$boolean, $context] = $condition;

            // first condition in group/query, no any AND, OR required
            if ($activeGroup) {
                // next conditions require AND or OR
                $activeGroup = false;
            } else {
                $statement .= $boolean;
                $statement .= ' ';
            }

            /*
             * When context is string it usually represent control keyword/syntax such as opening
             * or closing braces.
             */
            if (is_string($context)) {
                if ($context === '(') {
                    // new where group.
                    $activeGroup = true;
                }

                $statement .= $context;
                continue;
            }

            if ($context instanceof FragmentInterface) {
                $statement .= $this->fragment($params, $q, $context);
                $statement .= ' ';
                continue;
            }

            // identifier can be column name, expression or even query builder
            $statement .= $this->name($params, $q, $context[0]);
            $statement .= ' ';
            $statement .= $this->condition($params, $q, $context);
            $statement .= ' ';
        }

        if ($activeGroup) {
            throw new CompilerException('Unable to build where statement, unclosed where group');
        }

        if (trim($statement, ' ()') === '') {
            return '';
        }

        return $statement;
    }

    /**
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param array           $context
     *
     * @return string
     */
    protected function condition(SpiralQueryParameters $params, SpiralQuoter $q, array $context): string
    {
        $operator = $context[1];
        $value = $context[2];

        if ($operator instanceof FragmentInterface) {
            $operator = $this->fragment($params, $q, $operator);
        } elseif (!is_string($operator)) {
            throw new CompilerException('Invalid operator type, string or fragment is expected');
        }

        if ($value instanceof FragmentInterface) {
            return $operator . ' ' . $this->fragment($params, $q, $value);
        }

        if (!$value instanceof ParameterInterface) {
            throw new CompilerException('Invalid value format, fragment or parameter is expected');
        }

        $placeholder = '?';
        if ($value->isArray()) {
            if ($operator === '=') {
                $operator = 'IN';
            } elseif ($operator === '!=') {
                $operator = 'NOT IN';
            }

            $placeholder = '(' . rtrim(str_repeat('? ,', count($value->getValue())), ', ') . ')';
            $params->push($value);
        } elseif ($value->isNull()) {
            if ($operator === '=') {
                $operator = 'IS';
            } elseif ($operator === '!=') {
                $operator = 'IS NOT';
            }

            $placeholder = 'NULL';
        } else {
            $params->push($value);
        }

        if ($operator === 'BETWEEN' || $operator === 'NOT BETWEEN') {
            $params->push($context[3]);

            // possibly support between nested queries
            return $operator . ' ? AND ?';
        }

        return $operator . ' ' . $placeholder;
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
        if ($expression === '') {
            return '';
        }

        if ($prefix !== "\n" && $prefix !== ' ') {
            $prefix .= ' ';
        }

        return $prefix . $expression . $postfix;
    }
}
\class_alias(Compiler::class, SpiralCompiler::class, false);
