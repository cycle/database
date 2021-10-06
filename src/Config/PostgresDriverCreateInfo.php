<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config;

use Cycle\Database\Config\Postgres\PostgresPDOConnectionInfo;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\Postgres\PostgresDriver;

/**
 * @template-extends DriverCreateInfo<PostgresPDOConnectionInfo>
 */
final class PostgresDriverCreateInfo extends DriverCreateInfo
{
    /**
     * Default public schema name for all postgres connections.
     *
     * @var non-empty-string
     */
    public const DEFAULT_SCHEMA = 'public';

    /**
     * @var non-empty-array<non-empty-string>
     * @psalm-readonly-allow-private-mutation
     */
    public array $schema;

    /**
     * Note: The {@see PostgresPDOConnectionInfo} PDO connection config may change
     *       to a common (like "PostgresConnectionInfo") one in the future.
     *
     * @param PostgresPDOConnectionInfo $connection
     * @param iterable<non-empty-string>|non-empty-string $schema
     *
     * {@inheritDoc}
     */
    public function __construct(
        PostgresPDOConnectionInfo $connection,
        iterable|string $schema = self::DEFAULT_SCHEMA,
        bool $reconnect = true,
        string $timezone = 'UTC',
        bool $queryCache = true,
        bool $readonlySchema = false,
        bool $readonly = false,
    ) {
        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct(
            connection: $connection,
            reconnect: $reconnect,
            timezone: $timezone,
            queryCache: $queryCache,
            readonlySchema: $readonlySchema,
            readonly: $readonly,
        );

        $this->schema = $this->bootSchema($schema);
    }

    /**
     * @param iterable<non-empty-string>|non-empty-string $schema
     * @return array<non-empty-string>
     */
    private function bootSchema(iterable|string $schema): array
    {
        // Cast any schema config variants to array
        $schema = match (true) {
            $schema instanceof \Traversable => \iterator_to_array($schema),
            \is_string($schema) => [$schema],
            default => $schema
        };

        // Fill array by default in case that result array is empty
        if ($schema === []) {
            $schema = [self::DEFAULT_SCHEMA];
        }

        // Remove schema duplications
        return \array_values(\array_unique($schema));
    }

    /**
     * {@inheritDoc}
     */
    public function getDriver(): DriverInterface
    {
        return new PostgresDriver($this);
    }
}
