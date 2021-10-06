<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config;

use Cycle\Database\Config\SQLite\PDOConnectionConfig;
use Cycle\Database\Config\SQLite\PDOMemoryConnectionConfig;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\SQLite\SQLiteDriver;

/**
 * @template-extends DriverConfig<PDOConnectionConfig>
 */
final class SQLiteDriverConfig extends DriverConfig
{
    /**
     * Note: The {@see PDOConnectionConfig} PDO connection config may change
     *       to a common (like "SQLiteConnectionInfo") one in the future.
     *
     * @param PDOConnectionConfig|null $connection
     *
     * {@inheritDoc}
     */
    public function __construct(
        ?PDOConnectionConfig $connection = null,
        bool $reconnect = true,
        string $timezone = 'UTC',
        bool $queryCache = true,
        bool $readonlySchema = false,
        bool $readonly = false,
    ) {
        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct(
            connection: $connection ?? new PDOMemoryConnectionConfig(),
            reconnect: $reconnect,
            timezone: $timezone,
            queryCache: $queryCache,
            readonlySchema: $readonlySchema,
            readonly: $readonly,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getDriver(): DriverInterface
    {
        return new SQLiteDriver($this);
    }
}
