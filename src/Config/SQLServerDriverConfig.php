<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config;

use Cycle\Database\Config\SQLServer\ConnectionConfig;
use Cycle\Database\Driver\SQLServer\SQLServerDriver;

/**
 * @template-extends DriverConfig<ConnectionConfig>
 */
class SQLServerDriverConfig extends DriverConfig
{
    /**
     * @param ConnectionConfig $connection
     *
     * {@inheritDoc}
     */
    public function __construct(
        ConnectionConfig $connection,
        string $driver = SQLServerDriver::class,
        bool $reconnect = true,
        string $timezone = 'UTC',
        bool $queryCache = true,
        bool $readonlySchema = false,
        bool $readonly = false,
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
        );
    }
}
