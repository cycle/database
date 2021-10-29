<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL;

use Cycle\Database\Driver\CachingCompilerInterface;
use Cycle\Database\Driver\Compiler;
use Cycle\Database\Driver\Quoter;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\QueryParameters;
use Spiral\Database\Query\QueryParameters as SpiralQueryParameters;
use Spiral\Database\Driver\Quoter as SpiralQuoter;

class_exists(SpiralQueryParameters::class);
class_exists(SpiralQuoter::class);

/**
 * MySQL syntax specific compiler.
 */
class MySQLCompiler extends Compiler implements CachingCompilerInterface
{
    /**
     * @param QueryParameters $params
     * @param Quoter          $q
     * @param array           $tokens
     * @return string
     */
    protected function insertQuery(SpiralQueryParameters $params, SpiralQuoter $q, array $tokens): string
    {
        if ($tokens['columns'] === []) {
            return sprintf(
                'INSERT INTO %s () VALUES ()',
                $this->name($params, $q, $tokens['table'], true)
            );
        }

        return parent::insertQuery($params, $q, $tokens);
    }

    /**
     * {@inheritdoc}
     *
     * @link http://dev.mysql.com/doc/refman/5.0/en/select.html#id4651990
     */
    protected function limit(SpiralQueryParameters $params, SpiralQuoter $q, int $limit = null, int $offset = null): string
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
