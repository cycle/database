<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres;

use Cycle\Database\Driver\CachingCompilerInterface;
use Cycle\Database\Driver\Compiler;
use Cycle\Database\Driver\Quoter;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\QueryParameters;

/**
 * Postgres syntax specific compiler.
 */
class PostgresCompiler extends Compiler implements CachingCompilerInterface
{
    /**
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param array           $tokens
     * @return string
     */
    protected function insertQuery(QueryParameters $params, Quoter $q, array $tokens): string
    {
        $result = parent::insertQuery($params, $q, $tokens);

        if ($tokens['return'] === null) {
            return $result;
        }

        return sprintf(
            '%s RETURNING %s',
            $result,
            $this->quoteIdentifier($tokens['return'])
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function distinct(QueryParameters $params, Quoter $q, $distinct): string
    {
        if ($distinct === false) {
            return '';
        }

        if (is_array($distinct) && isset($distinct['on'])) {
            return sprintf('DISTINCT ON (%s)', $this->name($params, $q, $distinct['on']));
        }

        if (is_string($distinct)) {
            return sprintf('DISTINCT (%s)', $this->name($params, $q, $distinct));
        }

        return 'DISTINCT';
    }

    /**
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param int|null        $limit
     * @param int|null        $offset
     * @return string
     */
    protected function limit(QueryParameters $params, Quoter $q, int $limit = null, int $offset = null): string
    {
        if ($limit === null && $offset === null) {
            return '';
        }

        $statement = '';
        if ($limit !== null) {
            $statement = 'LIMIT ? ';
            $params->push(new Parameter($limit));
        }

        if ($offset !== null) {
            $statement .= 'OFFSET ?';
            $params->push(new Parameter($offset));
        }

        return trim($statement);
    }
}
