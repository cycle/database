<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config;

use Cycle\Database\Config\MySQL\ConnectionConfig;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\MySQL\MySQLDriver;

/**
 * @template-extends DriverConfig<ConnectionConfig>
 */
final class MySQLDriverConfig extends DriverConfig
{
    /**
     * @param ConnectionConfig $connection
     *
     * {@inheritDoc}
     */
    public function __construct(
        ConnectionConfig $connection,
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
    }

    /**
     * {@inheritDoc}
     */
    public function getDriver(): DriverInterface
    {
        return new MySQLDriver($this);
    }
}
