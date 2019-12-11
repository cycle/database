<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\SQLite;

use Spiral\Database\Driver\Compiler as AbstractCompiler;
use Spiral\Database\Driver\QueryBindings;
use Spiral\Database\Exception\CompilerException;
use Spiral\Database\Injection\ParameterInterface;

/**
 * SQLite specific syntax compiler.
 */
class SQLiteCompiler extends AbstractCompiler
{
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
        // FOR UPDATE is not available
        return parent::compileSelect(
            $bindings,
            $fromTables,
            $distinct,
            $columns,
            $joinTokens,
            $whereTokens,
            $havingTokens,
            $grouping,
            $orderBy,
            $limit,
            $offset,
            $unionTokens,
            false
        );
    }

    /**
     * {@inheritdoc}
     *
     * @see http://stackoverflow.com/questions/1609637/is-it-possible-to-insert-multiple-rows-at-a-time-in-an-sqlite-database
     */
    public function compileInsert(
        QueryBindings $bindings,
        string $table,
        array $columns,
        array $values
    ): string {
        // @todo possibly different statement for versions higher than 3.7.11
        if (count($values) === 1) {
            return parent::compileInsert($bindings, $table, $columns, $values);
        }

        //SQLite uses alternative syntax
        $statement = [];
        $statement[] = sprintf(
            'INSERT INTO %s (%s)',
            $this->quote($bindings, $table, true),
            $this->compileColumns($bindings, $columns)
        );

        foreach ($values as $rowset) {
            if (count($statement) !== 1) {
                // It is critically important to use UNION ALL, UNION will try to merge values together
                // which will cause non predictable insert order
                $statement[] = sprintf(
                    'UNION ALL SELECT %s',
                    trim($this->compileValue($bindings, $rowset), '()')
                );
                continue;
            }

            $selectColumns = [];

            if (!$rowset instanceof ParameterInterface || !$rowset->isArray()) {
                throw new CompilerException('Update parameter expected to be parametric array');
            }

            $rowset = $rowset->getValue();
            foreach ($columns as $index => $column) {
                $selectColumns[] = sprintf(
                    '%s AS %s',
                    $this->compileValue($bindings, $rowset[$index]),
                    $this->quote($bindings, $column)
                );
            }

            $statement[] = 'SELECT ' . implode(', ', $selectColumns);
        }

        return implode("\n", $statement);
    }

    /**
     * {@inheritdoc}
     *
     * @link http://stackoverflow.com/questions/10491492/sqllite-with-skip-offset-only-not-limit
     */
    protected function compileLimit(QueryBindings $bindings, int $limit, int $offset): string
    {
        if (empty($limit) && empty($offset)) {
            return '';
        }

        $statement = '';

        if (!empty($limit) || !empty($offset)) {
            $statement = 'LIMIT ' . ($limit ?: '-1') . ' ';
        }

        if (!empty($offset)) {
            $statement .= "OFFSET {$offset}";
        }

        return trim($statement);
    }
}
