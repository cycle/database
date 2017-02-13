<?php
/**
 * Spiral Framework.
 *
 * @license MIT
 * @author  Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Configs;

use Spiral\Core\InjectableConfig;
use Spiral\Core\Traits\Config\AliasTrait;

/**
 * Databases config.
 */
class DatabasesConfig extends InjectableConfig
{
    use AliasTrait;

    /**
     * Configuration section.
     */
    const CONFIG = 'databases';

    /**
     * @invisible
     * @var array
     */
    protected $config = [
        'default'     => 'default',
        'aliases'     => [],
        'databases'   => [],
        'connections' => [],
    ];

    /**
     * @return string
     */
    public function defaultDatabase(): string
    {
        return $this->config['default'];
    }

    /**
     * @param string $database
     *
     * @return bool
     */
    public function hasDatabase(string $database): bool
    {
        return isset($this->config['databases'][$database]);
    }

    /**
     * @param string $connection
     *
     * @return bool
     */
    public function hasDriver(string $connection): bool
    {
        return isset($this->config['connections'][$connection]);
    }

    /**
     * @return array
     */
    public function databaseNames(): array
    {
        return array_keys($this->config['databases']);
    }

    /**
     * @return array
     */
    public function driverNames(): array
    {
        return array_keys($this->config['connections']);
    }

    /**
     * @param string $database
     * @param string $id Connection id/name.
     *
     * @return string
     */
    public function databaseDriver(string $database, string $id = 'connection'): string
    {
        return $this->config['databases'][$database][$id];
    }

    /**
     * @param string $database
     *
     * @return string
     */
    public function databasePrefix(string $database): string
    {
        if (isset($this->config['databases'][$database]['tablePrefix'])) {
            return $this->config['databases'][$database]['tablePrefix'];
        }

        return '';
    }

    /**
     * @param string $driver
     *
     * @return string
     */
    public function driverClass(string $driver): string
    {
        return $this->config['connections'][$driver]['driver'];
    }

    /**
     * @param string $driver
     *
     * @return array
     */
    public function driverOptions(string $driver): array
    {
        $options = $this->config['connections'][$driver];
        unset($options['driver']);

        return $options;
    }
}
