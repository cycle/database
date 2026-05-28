<?php

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer;

use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Query\OnConflict;

/**
 * SQLServer-specific conflict-resolution policy.
 *
 * Currently adds no extra fields beyond the base — exists as a parking spot for
 * future SQLServer-specific options (e.g., MERGE WHEN MATCHED AND `<predicate>`)
 * and as a symmetric counterpart to {@see \Cycle\Database\Driver\Postgres\PostgresOnConflict}
 * and {@see \Cycle\Database\Driver\MySQL\MySQLOnConflict}.
 */
final class SQLServerOnConflict extends OnConflict
{
    public static function from(OnConflict $options): static
    {
        if ($options instanceof self) {
            return $options;
        }

        if ($options::class !== OnConflict::class) {
            throw new BuilderException(\sprintf(
                'Cannot narrow %s to %s. Use the base OnConflict, or %s directly.',
                $options::class,
                self::class,
                self::class,
            ));
        }

        return new self(
            target: $options->getTarget(),
            action: $options->getAction(),
            update: $options->getUpdate(),
        );
    }
}
