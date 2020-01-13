<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\SQLite;

use Spiral\Database\Driver\CachingCompilerInterface;
use Spiral\Database\Driver\Compiler;
use Spiral\Database\Driver\Quoter;
use Spiral\Database\Exception\CompilerException;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;
use Spiral\Database\Query\QueryParameters;

class SQLiteCompiler extends Compiler implements CachingCompilerInterface
{
    /**
     * {@inheritdoc}
     *
     * @link http://stackoverflow.com/questions/10491492/sqllite-with-skip-offset-only-not-limit
     */
    protected function limit(QueryParameters $params, Quoter $q, int $limit = null, int $offset = null): string
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

        return trim($statement);
    }

    /**
     * @inheritDoc
     */
    protected function selectQuery(QueryParameters $params, Quoter $q, array $tokens): string
    {
        // FOR UPDATE is not available
        $tokens['forUpdate'] = false;

        return parent::selectQuery($params, $q, $tokens);
    }

    /**
     * {@inheritdoc}
     *
     * @see http://stackoverflow.com/questions/1609637/is-it-possible-to-insert-multiple-rows-at-a-time-in-an-sqlite-database
     */
    protected function insertQuery(QueryParameters $params, Quoter $q, array $tokens): string
    {
        // @todo possibly different statement for versions higher than 3.7.11
        if (count($tokens['values']) === 1) {
            return parent::insertQuery($params, $q, $tokens);
        }

        // SQLite uses alternative syntax
        $statement = [];
        $statement[] = sprintf(
            'INSERT INTO %s (%s)',
            $this->name($params, $q, $tokens['table'], true),
            $this->columns($params, $q, $tokens['columns'])
        );

        foreach ($tokens['values'] as $rowset) {
            if (count($statement) !== 1) {
                // It is critically important to use UNION ALL, UNION will try to merge values together
                // which will cause non predictable insert order
                $statement[] = sprintf(
                    'UNION ALL SELECT %s',
                    trim($this->value($params, $q, $rowset), '()')
                );
                continue;
            }

            $selectColumns = [];

            if ($rowset instanceof ParameterInterface && $rowset->isArray()) {
                $rowset = $rowset->getValue();
            }

            if (!is_array($rowset)) {
                throw new CompilerException(
                    'Insert parameter expected to be parametric array'
                );
            }

            foreach ($tokens['columns'] as $index => $column) {
                $selectColumns[] = sprintf(
                    '%s AS %s',
                    $this->value($params, $q, $rowset[$index]),
                    $this->name($params, $q, $column)
                );
            }

            $statement[] = 'SELECT ' . implode(', ', $selectColumns);
        }

        return implode("\n", $statement);
    }
}
