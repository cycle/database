<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config;

use Cycle\Database\Config\SQLite\ConnectionConfig;
use Cycle\Database\Config\SQLite\MemoryConnectionConfig;
use Cycle\Database\Driver\SQLite\SQLiteDriver;

/**
 * @template-extends DriverConfig<ConnectionConfig>
 */
class SQLiteDriverConfig extends DriverConfig
{
    public function __construct(
        ?ConnectionConfig $connection = null,
        string $driver = SQLiteDriver::class,
        bool $reconnect = true,
        string $timezone = 'UTC',
        bool $queryCache = true,
        bool $readonlySchema = false,
        bool $readonly = false,
        array $options = [],
    ) {
        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct(
            connection: $connection ?? new MemoryConnectionConfig(),
            driver: $driver,
            reconnect: $reconnect,
            timezone: $timezone,
            queryCache: $queryCache,
            readonlySchema: $readonlySchema,
            readonly: $readonly,
            options: $options,
        );
    }
}
