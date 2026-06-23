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
use Cycle\Database\Query\ConflictAction;
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
     * Compile UPSERT as `INSERT ... AS <alias> ON DUPLICATE KEY UPDATE col = <alias>.col`.
     * Requires MySQL 8.0.19+ (row-alias syntax). The alias defaults to
     * {@see MySQLOnConflict::DEFAULT_ROW_ALIAS}; customize via
     * {@see MySQLOnConflict::withRowAlias()} if it collides with a real column name.
     */
    protected function upsertQuery(QueryParameters $params, Quoter $q, array $tokens): string
    {
        $onConflict = MySQLOnConflict::from($this->requireOnConflict($tokens));

        if ($tokens['columns'] === []) {
            throw new CompilerException('Upsert query must define at least one column.');
        }

        $values = [];
        foreach ($tokens['values'] as $value) {
            $values[] = $this->value($params, $q, $value);
        }

        $base = \sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->name($params, $q, $tokens['table'], true),
            $this->columns($params, $q, $tokens['columns']),
            \implode(', ', $values),
        );

        if ($onConflict->getAction() === ConflictAction::Nothing) {
            // MySQL has no DO NOTHING — emulate with a no-op self-assignment on the
            // conflict-target column (or the first inserted column as a fallback).
            // No row alias is emitted here: without `AS <alias>` the bare `col = col`
            // is unambiguous; with the alias in scope MySQL rejects it as ambiguous.
            $target = $onConflict->getTarget();
            $noopColumn = $target[0] ?? $tokens['columns'][0];
            $name = $this->name($params, $q, $noopColumn);
            return $base . ' ON DUPLICATE KEY UPDATE ' . \sprintf('%s = %s', $name, $name);
        }

        // DO UPDATE references the inserted row via `col = <alias>.col`, so the alias is required.
        $rowAlias = $onConflict->getRowAlias();
        $head = $base . ' AS ' . $this->quoteIdentifier($rowAlias);

        $updates = $this->upsertUpdateClause(
            $params,
            $q,
            $tokens['columns'],
            $onConflict->getTarget(),
            $onConflict->getUpdate(),
            $rowAlias,
        );

        return $head . ' ON DUPLICATE KEY UPDATE ' . $updates;
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
