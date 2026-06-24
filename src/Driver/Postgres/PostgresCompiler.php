<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres;

use Cycle\Database\Driver\CachingCompilerInterface;
use Cycle\Database\Driver\Compiler;
use Cycle\Database\Driver\Postgres\Injection\CompileJson;
use Cycle\Database\Driver\Quoter;
use Cycle\Database\Exception\CompilerException;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\ConflictAction;
use Cycle\Database\Query\QueryParameters;

/**
 * Postgres syntax specific compiler.
 */
class PostgresCompiler extends Compiler implements CachingCompilerInterface
{
    protected const ORDER_OPTIONS = [
        'ASC', 'ASC NULLS LAST', 'ASC NULLS FIRST' ,
        'DESC', 'DESC NULLS LAST', 'DESC NULLS FIRST',
    ];

    /**
     * @psalm-return non-empty-string
     */
    protected function insertQuery(QueryParameters $params, Quoter $q, array $tokens): string
    {
        $result = parent::insertQuery($params, $q, $tokens);

        return $this->appendReturning($params, $q, $result, $tokens);
    }

    /**
     * @psalm-return non-empty-string
     */
    protected function upsertQuery(QueryParameters $params, Quoter $q, array $tokens): string
    {
        $onConflict = PostgresOnConflict::from($this->requireOnConflict($tokens));

        if ($tokens['columns'] === []) {
            throw new CompilerException('Upsert query must define at least one column.');
        }

        $values = [];
        foreach ($tokens['values'] as $value) {
            $values[] = $this->value($params, $q, $value);
        }

        $head = \sprintf(
            'INSERT INTO %s (%s) VALUES %s ON CONFLICT %s',
            $this->name($params, $q, $tokens['table'], true),
            $this->columns($params, $q, $tokens['columns']),
            \implode(', ', $values),
            $this->postgresConflictTarget($params, $q, $onConflict),
        );

        if ($onConflict->getAction() === ConflictAction::Nothing) {
            return $this->appendReturning($params, $q, $head . ' DO NOTHING', $tokens);
        }

        $updates = $this->upsertUpdateClause(
            $params,
            $q,
            $tokens['columns'],
            $onConflict->getTarget(),
            $onConflict->getUpdate(),
            'EXCLUDED',
        );

        return $this->appendReturning($params, $q, $head . ' DO UPDATE SET ' . $updates, $tokens);
    }

    protected function distinct(QueryParameters $params, Quoter $q, string|bool|array $distinct): string
    {
        if ($distinct === false) {
            return '';
        }

        if (\is_array($distinct) && isset($distinct['on'])) {
            return \sprintf('DISTINCT ON (%s)', $this->name($params, $q, $distinct['on']));
        }

        if (\is_string($distinct)) {
            return \sprintf('DISTINCT (%s)', $this->name($params, $q, $distinct));
        }

        return 'DISTINCT';
    }

    protected function limit(QueryParameters $params, Quoter $q, ?int $limit = null, ?int $offset = null): string
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

        return \trim($statement);
    }

    protected function compileJsonOrderBy(string $path): FragmentInterface
    {
        return new CompileJson($path);
    }

    /**
     * @psalm-return non-empty-string
     */
    private function postgresConflictTarget(QueryParameters $params, Quoter $q, PostgresOnConflict $onConflict): string
    {
        $predicate = $onConflict->getIndexPredicate();

        $constraint = $onConflict->getConstraint();
        if ($constraint !== null) {
            $predicate === [] or throw new CompilerException(
                'ON CONFLICT ON CONSTRAINT cannot be combined with an index-inference predicate (targetWhere()).',
            );

            return 'ON CONSTRAINT ' . $this->quoteIdentifier($constraint);
        }

        $target = $onConflict->getTarget();
        $target === [] and throw new CompilerException(
            'Upsert query must define a conflict target (columns or constraint).',
        );

        $result = \sprintf('(%s)', $this->columns($params, $q, $target));

        if ($predicate === []) {
            return $result;
        }

        $where = \trim($this->where($params, $q, $predicate));

        return $where === '' ? $result : $result . ' WHERE ' . $where;
    }

    /**
     * @psalm-return non-empty-string
     */
    private function appendReturning(QueryParameters $params, Quoter $q, string $query, array $tokens): string
    {
        if (empty($tokens['return'])) {
            return $query;
        }

        return \sprintf(
            '%s RETURNING %s',
            $query,
            \implode(',', \array_map(
                fn(string|FragmentInterface|null $return) => $return instanceof FragmentInterface
                    ? $this->fragment($params, $q, $return)
                    : $this->quoteIdentifier($return),
                $tokens['return'],
            )),
        );
    }
}
