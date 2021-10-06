<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config;

use Cycle\Database\Config\SQLite\SQLitePDOConnectionInfo;
use Cycle\Database\Config\SQLite\SQLitePDOMemoryConnectionInfo;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\SQLite\SQLiteDriver;

/**
 * @template-extends DriverCreateInfo<SQLitePDOConnectionInfo>
 */
final class SQLiteDriverCreateInfo extends DriverCreateInfo
{
    /**
     * Note: The {@see SQLitePDOConnectionInfo} PDO connection config may change
     *       to a common (like "SQLiteConnectionInfo") one in the future.
     *
     * @param SQLitePDOConnectionInfo|null $connection
     *
     * {@inheritDoc}
     */
    public function __construct(
        ?SQLitePDOConnectionInfo $connection = null,
        bool $reconnect = true,
        string $timezone = 'UTC',
        bool $queryCache = true,
        bool $readonlySchema = false,
        bool $readonly = false,
    ) {
        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct(
            connection: $connection ?? new SQLitePDOMemoryConnectionInfo(),
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
