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
use Cycle\Database\Driver\MySQL\Injection\CompileJson;
use Cycle\Database\Driver\Quoter;
use Cycle\Database\Exception\CompilerException;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\QueryParameters;

/**
 * MySQL syntax specific compiler.
 */
class MySQLCompiler extends Compiler implements CachingCompilerInterface
{
    protected function insertQuery(QueryParameters $params, Quoter $q, array $tokens): string
    {
        if ($tokens['columns'] === []) {
            return \sprintf(
                'INSERT INTO %s () VALUES ()',
                $this->name($params, $q, $tokens['table'], true),
            );
        }

        return parent::insertQuery($params, $q, $tokens);
    }

    /**
     * @psalm-return non-empty-string
     */
    protected function upsertQuery(QueryParameters $params, Quoter $q, array $tokens): string
    {
        if (\count($tokens['columns']) === 0) {
            throw new CompilerException('Upsert query must define at least one column');
        }

        $values = [];

        foreach ($tokens['values'] as $value) {
            $values[] = $this->value($params, $q, $value);
        }

        $updates = \array_map(
            function ($column) use ($params, $q) {
                $name   = $this->name($params, $q, $column);
                return \sprintf('%s = VALUES(%s)', $name, $name);
            },
            $tokens['columns'],
        );

        return \sprintf(
            'INSERT INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE %s',
            $this->name($params, $q, $tokens['table'], true),
            $this->columns($params, $q, $tokens['columns']),
            \implode(', ', $values),
            \implode(', ', $updates),
        );
    }

    /**
     *
     *
     * @link http://dev.mysql.com/doc/refman/5.0/en/select.html#id4651990
     */
    protected function limit(QueryParameters $params, Quoter $q, ?int $limit = null, ?int $offset = null): string
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

        return \trim($statement);
    }

    protected function compileJsonOrderBy(string $path): FragmentInterface
    {
        return new CompileJson($path);
    }
}
