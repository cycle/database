<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\MySQL;

use Spiral\Database\Driver\CachingCompilerInterface;
use Spiral\Database\Driver\Compiler;
use Spiral\Database\Driver\Quoter;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Query\QueryParameters;

/**
 * MySQL syntax specific compiler.
 */
class MySQLCompiler extends Compiler implements CachingCompilerInterface
{
    /**
     * {@inheritdoc}
     *
     * @link http://dev.mysql.com/doc/refman/5.0/en/select.html#id4651990
     */
    protected function limit(QueryParameters $params, Quoter $q, int $limit = null, int $offset = null): string
    {
        if ($limit === null && $offset === null) {
            return '';
        }

        $statement = '';
        if ($limit === null) {
            // When limit is not provided (or 0) but offset does we can replace
            // limit value with PHP_INT_MAX
            $statement .= 'LIMIT 18446744073709551615 ';
        } else {
            $statement .= 'LIMIT ? ';
            $params->push(new Parameter($limit));
        }

        if ($offset !== null) {
            $statement .= 'OFFSET ?';
            $params->push(new Parameter($offset));
        }

        return trim($statement);
    }
}
