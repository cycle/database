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
use Spiral\Database\Driver\Quoter;
use Spiral\Database\Injection\Fragment;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Query\QueryParameters;

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
    protected function selectQuery(QueryParameters $params, Quoter $q, array $tokens): string
    {
        $limit = $tokens['limit'];
        $offset = $tokens['offset'];

        if (($limit === null && $offset === null) || $tokens['orderBy'] !== []) {
            //When no limits are specified we can use normal query syntax
            return call_user_func_array([$this, 'baseSelect'], func_get_args());
        }

        /**
         * We are going to use fallback mechanism here in order to properly select limited data from
         * database. Avoid usage of LIMIT/OFFSET without proper ORDER BY statement.
         *
         * Please see set of alerts raised in SelectQuery builder.
         */
        $tokens['columns'][] = new Fragment(
            sprintf(
                'ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS %s',
                $this->name($params, $q, self::ROW_NUMBER)
            )
        );

        $tokens['limit'] = null;
        $tokens['offset'] = null;

        return sprintf(
            "SELECT * FROM (\n%s\n) AS [ORD_FALLBACK] %s",
            $this->baseSelect($params, $q, $tokens),
            $this->limit($params, $q, $limit, $offset, self::ROW_NUMBER)
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
    protected function limit(
        QueryParameters $params,
        Quoter $q,
        int $limit = null,
        int $offset = null,
        string $rowNumber = null
    ): string {
        if ($limit === null && $offset === null) {
            return '';
        }

        //Modern SQLServer are easier to work with
        if ($rowNumber === null) {
            $statement = 'OFFSET ? ROWS ';
            $params->push(new Parameter((int)$offset));

            if ($limit !== null) {
                $statement .= 'FETCH FIRST ? ROWS ONLY';
                $params->push(new Parameter($limit));
            }

            return trim($statement);
        }

        $statement = "WHERE {$this->name($params, $q, $rowNumber)} ";

        //0 = row_number(1)
        ++$offset;

        if ($limit !== null) {
            $statement .= 'BETWEEN ? AND ?';
            $params->push(new Parameter((int)$offset));
            $params->push(new Parameter($offset + $limit - 1));
        } else {
            $statement .= '>= ?';
            $params->push(new Parameter((int)$offset));
        }

        return $statement;
    }

    /**
     * @inheritDoc
     */
    private function baseSelect(QueryParameters $params, Quoter $q, array $tokens): string
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
            $this->optional(' ', $tokens['forUpdate'] ? 'WITH (UPDLOCK,ROWLOCK)' : '', ' '),
            $this->optional(' ', $joins, ' '),
            $this->optional("\nWHERE", $this->where($params, $q, $tokens['where'])),
            $this->optional("\nGROUP BY", $this->groupBy($params, $q, $tokens['groupBy']), ' '),
            $this->optional("\nHAVING", $this->where($params, $q, $tokens['having'])),
            $this->optional("\n", $this->unions($params, $q, $tokens['union'])),
            $this->optional("\nORDER BY", $this->orderBy($params, $q, $tokens['orderBy'])),
            $this->optional("\n", $this->limit($params, $q, $tokens['limit'], $tokens['offset']))
        );
    }
}
