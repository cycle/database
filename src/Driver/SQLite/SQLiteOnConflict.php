<?php

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLite;

use Cycle\Database\Driver\Postgres\PostgresOnConflict;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Query\OnConflictWithPredicate;

/**
 * SQLite-specific conflict-resolution policy.
 *
 * SQLite's upsert syntax is modelled on Postgres, so it supports the
 * `ON CONFLICT (cols) WHERE <predicate>` index-inference form via
 * {@see OnConflictWithPredicate::targetWhere()}. It does NOT support
 * `ON CONFLICT ON CONSTRAINT`, so narrowing a {@see PostgresOnConflict} that
 * carries a constraint into this type is rejected.
 */
final class SQLiteOnConflict extends OnConflictWithPredicate
{
    public static function from(OnConflict $options): static
    {
        if ($options instanceof self) {
            return $options;
        }

        if ($options instanceof PostgresOnConflict && $options->getConstraint() !== null) {
            throw new BuilderException(
                'SQLite does not support ON CONFLICT ON CONSTRAINT; use a column target instead.',
            );
        }

        if (!$options instanceof OnConflictWithPredicate && $options::class !== OnConflict::class) {
            throw new BuilderException(\sprintf(
                'Cannot narrow %s to %s. Use the base OnConflict, %s, or %s directly.',
                $options::class,
                self::class,
                self::class,
                PostgresOnConflict::class,
            ));
        }

        return new self(
            target: $options->getTarget(),
            action: $options->getAction(),
            update: $options->getUpdate(),
            indexPredicate: $options instanceof OnConflictWithPredicate ? $options->getIndexPredicate() : [],
        );
    }
}
