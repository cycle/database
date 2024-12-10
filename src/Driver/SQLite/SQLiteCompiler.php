<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLite;

use Cycle\Database\Driver\CachingCompilerInterface;
use Cycle\Database\Driver\Compiler;
use Cycle\Database\Driver\Quoter;
use Cycle\Database\Driver\SQLite\Injection\CompileJson;
use Cycle\Database\Exception\CompilerException;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Injection\ParameterInterface;
use Cycle\Database\Query\QueryParameters;

class SQLiteCompiler extends Compiler implements CachingCompilerInterface
{
    /**
     *
     *
     * @link http://stackoverflow.com/questions/10491492/sqllite-with-skip-offset-only-not-limit
     */
    protected function limit(QueryParameters $params, Quoter $q, ?int $limit = null, ?int $offset = null): string
    {
        if ($limit === null && $offset === null) {
            return '';
        }

        if ($limit === null) {
            $statement = 'LIMIT -1 ';
        } else {
            $statement = 'LIMIT ? ';
            $params->push(new Parameter($limit));
        }

        if ($offset !== null) {
            $statement .= 'OFFSET ?';
            $params->push(new Parameter($offset));
        }

        return \trim($statement);
    }

    protected function selectQuery(QueryParameters $params, Quoter $q, array $tokens): string
    {
        // FOR UPDATE is not available
        $tokens['forUpdate'] = false;

        return parent::selectQuery($params, $q, $tokens);
    }

    /**
     *
     *
     * @see http://stackoverflow.com/questions/1609637/is-it-possible-to-insert-multiple-rows-at-a-time-in-an-sqlite-database
     */
    protected function insertQuery(QueryParameters $params, Quoter $q, array $tokens): string
    {
        if ($tokens['columns'] === []) {
            return \sprintf(
                'INSERT INTO %s DEFAULT VALUES',
                $this->name($params, $q, $tokens['table'], true),
            );
        }

        // @todo possibly different statement for versions higher than 3.7.11
        if (\count($tokens['values']) === 1) {
            return parent::insertQuery($params, $q, $tokens);
        }

        // SQLite uses alternative syntax
        $statement = [];
        $statement[] = \sprintf(
            'INSERT INTO %s (%s)',
            $this->name($params, $q, $tokens['table'], true),
            $this->columns($params, $q, $tokens['columns']),
        );

        foreach ($tokens['values'] as $rowset) {
            if (\count($statement) !== 1) {
                // It is critically important to use UNION ALL, UNION will try to merge values together
                // which will cause non predictable insert order
                $statement[] = \sprintf(
                    'UNION ALL SELECT %s',
                    \trim($this->value($params, $q, $rowset), '()'),
                );
                continue;
            }

            $selectColumns = [];

            if ($rowset instanceof ParameterInterface && $rowset->isArray()) {
                $rowset = $rowset->getValue();
            }

            if (!\is_array($rowset)) {
                throw new CompilerException(
                    'Insert parameter expected to be parametric array',
                );
            }

            foreach ($tokens['columns'] as $index => $column) {
                $selectColumns[] = \sprintf(
                    '%s AS %s',
                    $this->value($params, $q, $rowset[$index]),
                    $this->name($params, $q, $column),
                );
            }

            $statement[] = 'SELECT ' . \implode(', ', $selectColumns);
        }

        return \implode("\n", $statement);
    }

    protected function compileJsonOrderBy(string $path): FragmentInterface
    {
        return new CompileJson($path);
    }
}
