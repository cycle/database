<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config;

use Cycle\Database\Driver\DriverInterface;

/**
 * Connection configuration described in DBAL config file. Any driver can be
 * used as data source for multiple databases as table prefix and quotation
 * defined on Database instance level.
 *
 * @template T of ConnectionConfig
 */
abstract class DriverConfig
{
    /**
     * @param T $connection
     * @param bool $reconnect Allow reconnects
     * @param non-empty-string $timezone All datetime objects will be converted
     *        relative to this timezone (must match with DB timezone!)
     * @param bool $queryCache Enables query caching
     * @param bool $readonlySchema Disable schema modifications
     * @param bool $readonly Disable write expressions
     */
    public function __construct(
        public ConnectionConfig $connection,
        public bool $reconnect = true,
        public string $timezone = 'UTC',
        public bool $queryCache = true,
        public bool $readonlySchema = false,
        public bool $readonly = false,
    ) {
    }

    /**
     * @return DriverInterface
     */
    abstract public function getDriver(): DriverInterface;
}
