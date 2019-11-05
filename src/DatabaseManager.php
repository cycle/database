<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database;

use Psr\Container\ContainerExceptionInterface;
use Spiral\Core\Container;
use Spiral\Core\Container\InjectorInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Core\FactoryInterface;
use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\Config\DatabasePartial;
use Spiral\Database\Driver\Driver;
use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Exception\DatabaseException;
use Spiral\Database\Exception\DBALException;

/**
 * Automatic factory and configurator for Drivers and Databases.
 *
 * Example:
 * $config = [
 *  'default'     => 'default',
 *  'aliases'     => [
 *      'default'  => 'primary',
 *      'database' => 'primary',
 *      'db'       => 'primary',
 *  ],
 *  'databases'   => [
 *      'primary'   => [
 *          'connection'  => 'mysql',
 *          'tablePrefix' => 'db_'
 *      ],
 *      'secondary' => [
 *          'connection'  => 'postgres',
 *          'tablePrefix' => '',
 *      ],
 *  ],
 *  'connections' => [
 *      'mysql'     => [
 *          'driver'     => Driver\MySQL\MySQLDriver::class,
 *          'options' => [
 *              'connection' => 'mysql:host=127.0.0.1;dbname=database',
 *              'username'   => 'mysql',
 *              'password'   => 'mysql',
 *           ],
 *      ],
 *      'postgres'  => [
 *          'driver'     => Driver\Postgres\PostgresDriver::class,
 *          'options' => [
 *              'connection' => 'pgsql:host=127.0.0.1;dbname=database',
 *              'username'   => 'postgres',
 *              'password'   => 'postgres',
 *           ],
 *      ],
 *      'runtime'   => [
 *          'driver'     => Driver\SQLite\SQLiteDriver::class,
 *          'options' => [
 *              'connection' => 'sqlite:' . directory('runtime') . 'runtime.db',
 *              'username'   => 'sqlite',
 *           ],
 *      ],
 *      'sqlServer' => [
 *          'driver'     => Driver\SQLServer\SQLServerDriver::class,
 *          'options' => [
 *              'connection' => 'sqlsrv:Server=OWNER;Database=DATABASE',
 *              'username'   => 'sqlServer',
 *              'password'   => 'sqlServer',
 *           ],
 *      ],
 *   ]
 * ];
 *
 * $manager = new DatabaseManager(new DatabaseConfig($config));
 *
 * echo $manager->database('runtime')->select()->from('users')->count();
 */
final class DatabaseManager implements DatabaseProviderInterface, SingletonInterface, InjectorInterface
{

    /**  @var FactoryInterface */
    protected $factory = null;
    /** @var DatabaseConfig */
    private $config = null;

    /** @var Database[] */
    private $databases = [];

    /** @var DriverInterface[] */
    private $drivers = [];

    /**
     * @param DatabaseConfig   $config
     * @param FactoryInterface $factory
     */
    public function __construct(DatabaseConfig $config, FactoryInterface $factory = null)
    {
        $this->config = $config;
        $this->factory = $factory ?? new Container();
    }

    /**
     * {@inheritdoc}
     */
    public function createInjection(\ReflectionClass $class, string $context = null)
    {
        // if context is empty default database will be returned
        return $this->database($context);
    }

    /**
     * Get all databases.
     *
     * @return Database[]
     *
     * @throws DatabaseException
     */
    public function getDatabases(): array
    {
        $names = array_unique(array_merge(array_keys($this->databases), array_keys($this->config->getDatabases())));

        $result = [];
        foreach ($names as $name) {
            $result[] = $this->database($name);
        }

        return $result;
    }

    /**
     * Get Database associated with a given database alias or automatically created one.
     *
     * @param string|null $database
     * @return Database|DatabaseInterface
     *
     * @throws DBALException
     */
    public function database(string $database = null): DatabaseInterface
    {
        if ($database === null) {
            $database = $this->config->getDefaultDatabase();
        }

        //Spiral support ability to link multiple virtual databases together using aliases
        $database = $this->config->resolveAlias($database);

        if (isset($this->databases[$database])) {
            return $this->databases[$database];
        }

        if (!$this->config->hasDatabase($database)) {
            throw new DBALException(
                "Unable to create Database, no presets for '{$database}' found"
            );
        }

        return $this->databases[$database] = $this->makeDatabase(
            $this->config->getDatabase($database)
        );
    }

    /**
     * Add new database.
     *
     * @param Database $database
     *
     * @throws DBALException
     */
    public function addDatabase(Database $database): void
    {
        if (isset($this->databases[$database->getName()])) {
            throw new DBALException("Database '{$database->getName()}' already exists");
        }

        $this->databases[$database->getName()] = $database;
    }

    /**
     * Get instance of every available driver/connection.
     *
     * @return Driver[]
     *
     * @throws DBALException
     */
    public function getDrivers(): array
    {
        $names = array_unique(array_merge(array_keys($this->drivers), array_keys($this->config->getDrivers())));

        $result = [];
        foreach ($names as $name) {
            $result[] = $this->driver($name);
        }

        return $result;
    }

    /**
     * Get driver instance by it's name or automatically create one.
     *
     * @param string $driver
     * @return DriverInterface
     *
     * @throws DBALException
     */
    public function driver(string $driver): DriverInterface
    {
        if (isset($this->drivers[$driver])) {
            return $this->drivers[$driver];
        }
        try {
            return $this->drivers[$driver] = $this->config->getDriver($driver)->resolve($this->factory);
        } catch (ContainerExceptionInterface $e) {
            throw new DBALException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Manually set connection instance.
     *
     * @param string          $name
     * @param DriverInterface $driver
     * @return self
     *
     * @throws DBALException
     */
    public function addDriver(string $name, DriverInterface $driver): DatabaseManager
    {
        if (isset($this->drivers[$name])) {
            throw new DBALException("Connection '{$name}' already exists");
        }

        $this->drivers[$name] = $driver;

        return $this;
    }

    /**
     * @param DatabasePartial $database
     * @return Database
     *
     * @throws DBALException
     */
    protected function makeDatabase(DatabasePartial $database): Database
    {
        return new Database(
            $database->getName(),
            $database->getPrefix(),
            $this->driver($database->getDriver()),
            $database->getReadDriver() ? $this->driver($database->getReadDriver()) : null
        );
    }
}
