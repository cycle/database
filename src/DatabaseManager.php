<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database;

use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Spiral\Core\Container;
use Spiral\Core\FactoryInterface;
use Cycle\Database\Config\DatabaseConfig;
use Spiral\Database\Config\DatabaseConfig as SpiralDatabaseConfig;
use Cycle\Database\Config\DatabasePartial;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Exception\DatabaseException;
use Cycle\Database\Exception\DBALException;
use Spiral\Logger\Traits\LoggerTrait;
use Spiral\Database\Config\DatabasePartial as SpiralDatabasePartial;
use Spiral\Database\Driver\DriverInterface as SpiralDriverInterface;
use Spiral\Database\Database as SpiralDatabase;
use Spiral\Database\DatabaseProviderInterface as SpiralDatabaseProviderInterface;

interface_exists(SpiralDriverInterface::class);
interface_exists(SpiralDatabaseProviderInterface::class);
class_exists(SpiralDatabasePartial::class);
class_exists(SpiralDatabase::class);
class_exists(SpiralDatabaseConfig::class);

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
final class DatabaseManager implements
    SpiralDatabaseProviderInterface,
    Container\SingletonInterface,
    Container\InjectorInterface
{
    use LoggerTrait {
        setLogger as protected internalSetLogger;
    }

    /** @var DatabaseConfig */
    private $config;

    /**  @var FactoryInterface */
    private $factory;

    /** @var Database[] */
    private $databases = [];

    /** @var DriverInterface[] */
    private $drivers = [];

    /**
     * Set logger for all drivers
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->internalSetLogger($logger);
        // Assign the logger to all initialized drivers
        foreach ($this->drivers as $driver) {
            if ($driver instanceof LoggerAwareInterface) {
                $driver->setLogger($this->logger);
            }
        }
    }

    /**
     * @param DatabaseConfig $config
     * @param FactoryInterface|null $factory
     */
    public function __construct(SpiralDatabaseConfig $config, FactoryInterface $factory = null)
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
        $names = array_unique(
            array_merge(
                array_keys($this->databases),
                array_keys($this->config->getDatabases())
            )
        );

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

        // Cycle support ability to link multiple virtual databases together
        // using aliases.
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
    public function addDatabase(SpiralDatabase $database): void
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
        $names = array_unique(
            array_merge(
                array_keys($this->drivers),
                array_keys($this->config->getDrivers())
            )
        );

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
            $driverObject = $this->config->getDriver($driver)->resolve($this->factory);
            $this->drivers[$driver] = $driverObject;

            if ($driverObject instanceof LoggerAwareInterface) {
                $logger = $this->getLogger(get_class($driverObject));
                if (!$logger instanceof NullLogger) {
                    $driverObject->setLogger($logger);
                }
            }

            return $this->drivers[$driver];
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
    public function addDriver(string $name, SpiralDriverInterface $driver): DatabaseManager
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
    protected function makeDatabase(SpiralDatabasePartial $database): Database
    {
        return new Database(
            $database->getName(),
            $database->getPrefix(),
            $this->driver($database->getDriver()),
            $database->getReadDriver() ? $this->driver($database->getReadDriver()) : null
        );
    }
}
