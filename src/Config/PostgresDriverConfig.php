<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config;

use Cycle\Database\Config\Postgres\ConnectionConfig;
use Cycle\Database\Driver\Postgres\PostgresDriver;

/**
 * @template-extends DriverConfig<ConnectionConfig>
 */
class PostgresDriverConfig extends DriverConfig
{
    /**
     * Default public schema name for all postgres connections.
     *
     * @var non-empty-string
     */
    public const DEFAULT_SCHEMA = 'public';

    /**
     * @var non-empty-array<non-empty-string>
     *
     * @psalm-readonly-allow-private-mutation
     */
    public array $schema;

    /**
     * @param iterable<non-empty-string>|non-empty-string $schema List of available Postgres
     *        schemas for "search path" (See also {@link https://www.postgresql.org/docs/9.6/ddl-schemas.html}).
     *        The first parameter's item will be used as default schema.
     *
     * {@inheritDoc}
     */
    public function __construct(
        ConnectionConfig $connection,
        iterable|string $schema = self::DEFAULT_SCHEMA,
        string $driver = PostgresDriver::class,
        bool $reconnect = true,
        string $timezone = 'UTC',
        bool $queryCache = true,
        bool $readonlySchema = false,
        bool $readonly = false,
        array $options = [],
    ) {
        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct(
            connection: $connection,
            driver: $driver,
            reconnect: $reconnect,
            timezone: $timezone,
            queryCache: $queryCache,
            readonlySchema: $readonlySchema,
            readonly: $readonly,
            options: $options,
        );

        $this->schema = $this->bootSchema($schema);
    }

    /**
     * @param iterable<non-empty-string>|non-empty-string $schema
     *
     * @return array<non-empty-string>
     */
    private function bootSchema(iterable|string $schema): array
    {
        // Cast any schema config variants to array
        $schema = match (true) {
            $schema instanceof \Traversable => \iterator_to_array($schema),
            \is_string($schema) => [$schema],
            default => $schema,
        };

        // Fill array by default in case that result array is empty
        if ($schema === []) {
            $schema = [self::DEFAULT_SCHEMA];
        }

        // Remove schema duplications
        return \array_values(\array_unique($schema));
    }
}
