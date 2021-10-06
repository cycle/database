<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config;

use Cycle\Database\Config\SQLServer\UriConnectionConfig;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\SQLServer\SQLServerDriver;

/**
 * @template-extends DriverConfig<UriConnectionConfig>
 */
final class SQLServerDriverConfig extends DriverConfig
{
    /**
     * Note: The {@see UriConnectionConfig} PDO connection config may change
     *       to a common (like "SQLServerConnectionInfo") one in the future.
     *
     * {@inheritDoc}
     */
    public function __construct(
        UriConnectionConfig $connection,
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
        return new SQLServerDriver($this);
    }
}
