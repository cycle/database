<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\DatabasePartial;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Exception\DatabaseException;
use Cycle\Database\Exception\DBALException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Automatic factory and configurator for Drivers and Databases.
 *
 * $manager = new DatabaseManager(new DatabaseConfig($config));
 *
 * echo $manager->database('runtime')->select()->from('users')->count();
 */
final class DatabaseManager implements DatabaseProviderInterface, LoggerAwareInterface
{
    /** @var Database[] */
    private array $databases = [];

    /** @var DriverInterface[] */
    private array $drivers = [];

    private ?LoggerInterface $logger = null;

    public function __construct(
        private DatabaseConfig $config,
        private ?LoggerFactoryInterface $loggerFactory = null,
    ) {}

    /**
     * Set logger for all drivers
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;

        // Assign the logger to all initialized drivers
        foreach ($this->drivers as $driver) {
            if ($driver instanceof LoggerAwareInterface) {
                $driver->setLogger($this->logger);
            }
        }
    }

    /**
     * Get all databases.
     *
     * @return Database[]
     * @throws DatabaseException
     *
     */
    public function getDatabases(): array
    {
        $names = \array_unique(
            \array_merge(
                \array_keys($this->databases),
                \array_keys($this->config->getDatabases()),
            ),
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
     * @throws DBALException
     */
    public function database(?string $database = null): DatabaseInterface
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

        $this->config->hasDatabase($database) or throw new DBALException(
            "Unable to create Database, no presets for '{$database}' found",
        );

        return $this->databases[$database] = $this->makeDatabase($this->config->getDatabase($database));
    }

    /**
     * Add new database.
     *
     * @throws DBALException
     */
    public function addDatabase(Database $database): void
    {
        isset($this->databases[$database->getName()]) and throw new DBALException(
            "Database '{$database->getName()}' already exists",
        );

        $this->databases[$database->getName()] = $database;
    }

    /**
     * Get instance of every available driver/connection.
     *
     * @return Driver[]
     * @throws DBALException
     *
     */
    public function getDrivers(): array
    {
        $names = \array_unique(
            \array_merge(
                \array_keys($this->drivers),
                \array_keys($this->config->getDrivers()),
            ),
        );

        $result = [];
        foreach ($names as $name) {
            $result[] = $this->driver($name);
        }

        return $result;
    }

    /**
     * Get driver instance.
     *
     * @psalm-param non-empty-string $driver
     */
    public function driver(string $driver): DriverInterface
    {
        if (isset($this->drivers[$driver])) {
            return $this->drivers[$driver];
        }

        $driverObject = $this->config->getDriver($driver);
        $this->drivers[$driver] = $driverObject;

        if ($driverObject instanceof LoggerAwareInterface) {
            $logger = $this->getLoggerForDriver($driverObject);
            if (!$logger instanceof NullLogger) {
                $driverObject->setLogger($logger);
            }
        }

        return $this->drivers[$driver];
    }

    /**
     * Manually set connection instance.
     *
     * @psalm-param non-empty-string $name
     *
     * @throws DBALException
     */
    public function addDriver(string $name, DriverInterface $driver): self
    {
        isset($this->drivers[$name]) and throw new DBALException("Connection '{$name}' already exists");

        $this->drivers[$name] = $driver;

        return $this;
    }

    /**
     * @throws DBALException
     */
    private function makeDatabase(DatabasePartial $database): Database
    {
        return new Database(
            $database->getName(),
            $database->getPrefix(),
            $this->driver($database->getDriver()),
            $database->getReadDriver() ? $this->driver($database->getReadDriver()) : null,
        );
    }

    private function getLoggerForDriver(DriverInterface $driver): LoggerInterface
    {
        if (!$this->loggerFactory) {
            return $this->logger ??= new NullLogger();
        }

        return $this->loggerFactory->getLogger($driver);
    }
}
