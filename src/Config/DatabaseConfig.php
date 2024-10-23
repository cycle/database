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
use Spiral\Core\InjectableConfig;
use Spiral\Core\Traits\Config\AliasTrait;

final class DatabaseConfig extends InjectableConfig
{
    use AliasTrait;

    public const CONFIG = 'database';
    public const DEFAULT_DATABASE = 'default';

    public function __construct(array $config = [])
    {
        parent::__construct(\array_merge([
            'default' => self::DEFAULT_DATABASE,
            'aliases' => [],
            'databases' => [],
            'connections' => [],
        ], $config));
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
            $config['readConnection'] ?? $config['read'] ?? $config['readDriver'] ?? null,
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
            ]),
        );
    }
}
