<?php

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\Fragment;

/**
 * Conflict-resolution policy that supports an index-inference predicate on the
 * conflict target — the `ON CONFLICT (cols) WHERE <predicate>` form used to match
 * a partial unique index.
 *
 * Only drivers whose upsert syntax inherits the Postgres `ON CONFLICT` inference
 * clause expose this: {@see \Cycle\Database\Driver\Postgres\PostgresOnConflict} and
 * {@see \Cycle\Database\Driver\SQLite\SQLiteOnConflict}. MySQL (`ON DUPLICATE KEY
 * UPDATE`) and SQL Server (`MERGE`) have no equivalent, which is why this feature
 * lives below the base {@see OnConflict} instead of on it.
 *
 * Because the feature set matches, these two subclasses convert into each other
 * through {@see self::from()} without error (see the subclasses for the one
 * exception: a Postgres constraint target cannot be narrowed to SQLite).
 *
 * The predicate is stored as a where-token array (the same shape SelectQuery uses),
 * so the compiler renders it through its regular where-renderer and the query cache
 * hashes it through its regular where-hasher.
 */
abstract class OnConflictWithPredicate extends OnConflict
{
    /**
     * @param list<non-empty-string> $target
     * @param list<non-empty-string>|array<non-empty-string, mixed>|null $update
     * @param array $indexPredicate Where tokens, see {@see OnConflictWhere}.
     */
    protected function __construct(
        array $target,
        ConflictAction $action,
        null|array $update,
        protected array $indexPredicate = [],
    ) {
        parent::__construct($target, $action, $update);
    }

    /**
     * Index-inference predicate for the conflict target — the `WHERE` in
     * `ON CONFLICT (cols) WHERE <predicate>`. Use it to match a partial unique index.
     *
     * This is NOT the `DO UPDATE ... WHERE` condition on the update branch.
     *
     * Accepts the same argument shapes as {@see \Cycle\Database\Query\Traits\WhereTrait::where()},
     * plus a raw-SQL shorthand:
     *  - single raw string — emitted verbatim, identifiers NOT quoted (the one shape
     *    `where()` cannot take): `->targetWhere('resource_key IS NOT NULL')`;
     *  - simple condition — `->targetWhere('resource_key', '!=', null)` (→ `IS NOT NULL`),
     *    `->targetWhere('priority', '>', 5)`, `->targetWhere('col', $value)`;
     *  - array form — `->targetWhere(['priority' => ['>' => 5], 'resource_key' => ['!=' => null]])`;
     *  - {@see \Cycle\Database\Injection\FragmentInterface} — e.g. an {@see \Cycle\Database\Injection\Expression};
     *  - {@see \Closure} — receives an {@see OnConflictWhere} builder for multi-condition
     *    AND/OR groups: `->targetWhere(fn(OnConflictWhere $w) => $w->where(...)->orWhere(...))`.
     *
     * Identifiers in every non-raw-string form are quoted; values are bound as parameters.
     *
     * @param mixed ...$args See {@see \Cycle\Database\Query\Traits\WhereTrait::where()}.
     */
    public function targetWhere(mixed ...$args): static
    {
        $args === [] and throw new BuilderException('targetWhere() requires at least one argument.');

        $clone = clone $this;

        // A single string is not a valid builder condition — treat it as raw SQL.
        if (\count($args) === 1 && \is_string($args[0])) {
            $args[0] === '' and throw new BuilderException('Index predicate must not be empty.');

            $clone->indexPredicate = [['AND', new Fragment($args[0])]];
            return $clone;
        }

        $builder = new OnConflictWhere();
        if (\count($args) === 1 && $args[0] instanceof \Closure) {
            ($args[0])($builder);
        } else {
            $builder->where(...$args);
        }

        $clone->indexPredicate = $builder->getWhereTokens();
        return $clone;
    }

    /**
     * Where tokens of the index-inference predicate (empty when none was set).
     */
    public function getIndexPredicate(): array
    {
        return $this->indexPredicate;
    }
}
