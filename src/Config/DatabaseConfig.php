<?php

/**
 * Spiral Framework.
 *
 * @license MIT
 * @author  Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Config;

use Spiral\Core\Container\Autowire;
use Spiral\Core\InjectableConfig;
use Spiral\Core\Traits\Config\AliasTrait;
use Spiral\Database\Exception\ConfigException;

final class DatabaseConfig extends InjectableConfig
{
    use AliasTrait;

    public const CONFIG           = 'database';
    public const DEFAULT_DATABASE = 'default';

    /**
     * @internal
     * @var array
     */
    protected $config = [
        'default'     => self::DEFAULT_DATABASE,
        'aliases'     => [],
        'databases'   => [],
        'connections' => [],
    ];

    /**
     * @return string
     */
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
        foreach (array_keys($this->config['databases'] ?? []) as $database) {
            $result[$database] = $this->getDatabase($database);
        }

        return $result;
    }

    /**
     * Get names list of all driver connections.
     *
     * @return Autowire[]
     */
    public function getDrivers(): array
    {
        $result = [];
        foreach (array_keys($this->config['connections'] ?? $this->config['drivers'] ?? []) as $driver) {
            $result[$driver] = $this->getDriver($driver);
        }

        return $result;
    }

    /**
     * @param string $database
     * @return bool
     */
    public function hasDatabase(string $database): bool
    {
        return isset($this->config['databases'][$database]);
    }

    /**
     * @param string $database
     * @return DatabasePartial
     *
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

    /**
     * @param string $driver
     * @return bool
     */
    public function hasDriver(string $driver): bool
    {
        return isset($this->config['connections'][$driver]) || isset($this->config['drivers'][$driver]);
    }

    /**
     * @param string $driver
     * @return Autowire
     *
     * @throws ConfigException
     */
    public function getDriver(string $driver): Autowire
    {
        if (!$this->hasDriver($driver)) {
            throw new ConfigException("Undefined driver `{$driver}`");
        }

        $config = $this->config['connections'][$driver] ?? $this->config['drivers'][$driver];
        if ($config instanceof Autowire) {
            return $config;
        }

        $options = $config;
        if (isset($config['options']) && $config['options'] !== []) {
            $options = $config['options'] + $config;
        }

        return new Autowire($config['driver'] ?? $config['class'], ['options' => $options]);
    }
}
