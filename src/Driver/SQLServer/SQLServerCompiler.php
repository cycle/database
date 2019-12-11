<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\SQLServer;

use Spiral\Database\Driver\Compiler;
use Spiral\Database\Driver\QueryBindings;
use Spiral\Database\Injection\Fragment;

/**
 * Microsoft SQL server specific syntax compiler.
 */
class SQLServerCompiler extends Compiler
{
    /**
     * Column to be used as ROW_NUMBER in fallback selection mechanism, attention! Amount of columns
     * in result set will be increaced by 1!
     */
    public const ROW_NUMBER = '_ROW_NUMBER_';

    /**
     * {@inheritdoc}
     *
     * Attention, limiting and ordering UNIONS will fail in SQL SERVER < 2012.
     * For future upgrades: think about using top command.
     *
     * @link http://stackoverflow.com/questions/603724/how-to-implement-limit-with-microsoft-sql-server
     * @link http://stackoverflow.com/questions/971964/limit-10-20-in-sql-server
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
        array $ordering = [],
        int $limit = 0,
        int $offset = 0,
        array $unionTokens = [],
        bool $forUpdate = false
    ): string {
        if (($limit === 0 && $offset === 0) || $ordering !== []) {
            //When no limits are specified we can use normal query syntax
            return call_user_func_array([$this, 'baseSelect'], func_get_args());
        }

        /**
         * We are going to use fallback mechanism here in order to properly select limited data from
         * database. Avoid usage of LIMIT/OFFSET without proper ORDER BY statement.
         *
         * Please see set of alerts raised in SelectQuery builder.
         */
        $columns[] = new Fragment(
            "ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS {$this->quote($bindings, self::ROW_NUMBER)}"
        );

        return sprintf(
            "SELECT * FROM (\n%s\n) AS [ORD_FALLBACK] %s",
            $this->baseSelect(
                $bindings,
                $fromTables,
                $distinct,
                $columns,
                $joinTokens,
                $whereTokens,
                $havingTokens,
                $grouping,
                [],
                0, //No limit or offset
                0, //No limit or offset
                $unionTokens,
                $forUpdate
            ),
            $this->compileLimit($bindings, $limit, $offset, self::ROW_NUMBER)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param string $rowNumber Row used in a fallback sorting mechanism, ONLY when no ORDER BY
     *                          specified.
     *
     * @link http://stackoverflow.com/questions/2135418/equivalent-of-limit-and-offset-for-sql-server
     */
    protected function compileLimit(QueryBindings $bindings, int $limit, int $offset, string $rowNumber = null): string
    {
        if (empty($limit) && empty($offset)) {
            return '';
        }

        //Modern SQLServer are easier to work with
        if (empty($rowNumber)) {
            $statement = "OFFSET {$offset} ROWS ";

            if (!empty($limit)) {
                $statement .= "FETCH FIRST {$limit} ROWS ONLY";
            }

            return trim($statement);
        }

        $statement = "WHERE {$this->quote($bindings, $rowNumber)} ";

        //0 = row_number(1)
        ++$offset;

        if (!empty($limit)) {
            $statement .= "BETWEEN {$offset} AND " . ($offset + $limit - 1);
        } else {
            $statement .= ">= {$offset}";
        }

        return $statement;
    }

    /**
     * @inheritDoc
     */
    private function baseSelect(
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
            $this->optional(' ', $forUpdate ? 'WITH (UPDLOCK,ROWLOCK)' : '', ' '),
            $this->optional(' ', $joinsStatement, ' '),
            $this->optional("\nWHERE", $this->compileWhere($bindings, $whereTokens)),
            $this->optional("\nGROUP BY", $this->compileGroupBy($bindings, $grouping), ' '),
            $this->optional("\nHAVING", $this->compileWhere($bindings, $havingTokens)),
            $this->optional("\n", $this->compileUnions($bindings, $unionTokens)),
            $this->optional("\nORDER BY", $this->compileOrderBy($bindings, $orderBy)),
            $this->optional("\n", $this->compileLimit($bindings, $limit, $offset))
        );
    }
}
