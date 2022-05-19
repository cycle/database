<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Exception\ConfigException;
use Cycle\Database\NamedInterface;
use Spiral\Core\ConfigsInterface;
use Spiral\Core\Container\InjectableInterface;
use Spiral\Core\Traits\Config\AliasTrait;

final class DatabaseConfig implements InjectableInterface, \IteratorAggregate, \ArrayAccess
{
    use AliasTrait;

    public const INJECTOR = ConfigsInterface::class;
    public const CONFIG = 'database';
    public const DEFAULT_DATABASE = 'default';

    /**
     * At this moment on array based configs can be supported.
     */
    public function __construct(
        private array $config = [
            'default' => self::DEFAULT_DATABASE,
            'aliases' => [],
            'databases' => [],
            'connections' => [],
        ]
    ) {
    }

    public function getDefaultDatabase(): string
    {
        return $this->config['default'] ?? 'default';
    }

    /**
     * Get named list of all databases.
     *
     * @return DatabasePartial[]
     */
    public function getDatabases(): array
    {
        $result = [];
        foreach (\array_keys($this->config['databases'] ?? []) as $database) {
            $result[$database] = $this->getDatabase($database);
        }

        return $result;
    }

    /**
     * Get names list of all driver connections.
     *
     * @return DriverInterface[]
     */
    public function getDrivers(): array
    {
        $result = [];
        foreach (\array_keys($this->config['connections'] ?? $this->config['drivers'] ?? []) as $driver) {
            $result[$driver] = $this->getDriver($driver);
        }

        return $result;
    }

    public function hasDatabase(string $database): bool
    {
        return isset($this->config['databases'][$database]);
    }

    /**
     * @throws ConfigException
     */
    public function getDatabase(string $database): DatabasePartial
    {
        if (!$this->hasDatabase($database)) {
            throw new ConfigException("Undefined database `{$database}`");
        }

        $config = $this->config['databases'][$database];

        return new DatabasePartial(
            $database,
            $config['tablePrefix'] ?? $config['prefix'] ?? '',
            $config['connection'] ?? $config['write'] ?? $config['driver'],
            $config['readConnection'] ?? $config['read'] ?? $config['readDriver'] ?? null
        );
    }

    public function hasDriver(string $driver): bool
    {
        return isset($this->config['connections'][$driver]) || isset($this->config['drivers'][$driver]);
    }

    /**
     * @throws ConfigException
     */
    public function getDriver(string $driver): DriverInterface
    {
        if (!$this->hasDriver($driver)) {
            throw new ConfigException("Undefined driver `{$driver}`");
        }

        $config = $this->config['connections'][$driver] ?? $this->config['drivers'][$driver];

        if ($config instanceof DriverConfig) {
            $driverObject = $config->driver::create($config);

            if ($driverObject instanceof NamedInterface) {
                return $driverObject->withName($driver);
            }

            return $driverObject;
        }

        throw new \InvalidArgumentException(
            \vsprintf('Driver config must be an instance of %s, but %s passed', [
                DriverConfig::class,
                \get_debug_type($config),
            ])
        );
    }

    public function toArray(): array
    {
        return $this->config;
    }

    public function offsetExists($offset)
    {
        return \array_key_exists($offset, $this->config);
    }

    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new \Spiral\Core\Exception\ConfigException("Undefined configuration key '{$offset}'");
        }

        return $this->config[$offset];
    }

    /**
     * @throws ConfigException
     */
    public function offsetSet($offset, $value): void
    {
        throw new ConfigException(
            'Unable to change configuration data, configs are treated as immutable by default'
        );
    }

    /**
     * @throws ConfigException
     */
    public function offsetUnset($offset): void
    {
        throw new ConfigException(
            'Unable to change configuration data, configs are treated as immutable by default'
        );
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->config);
    }
}
