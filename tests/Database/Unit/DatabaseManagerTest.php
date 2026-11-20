<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Database;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Exception\DBALException;
use Mockery as m;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class DatabaseManagerTest
{
    public const DEFAULT_OPTIONS = [
        'default' => 'default',
        'databases' => [
            'default' => [
                'prefix' => 'prefix_',
                'read' => 'read',
                'write' => 'write',
            ],
        ],
        'connections' => [],
    ];

    public function testAddDatabase(): void
    {
        $driver = m::mock(DriverInterface::class);
        $db = new Database('default', '', $driver);


        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDatabase($db);

        Assert::same($dbal->database('default'), $db);
    }

    public function testAddDatabaseException(): void
    {
        $driver = m::mock(DriverInterface::class);
        $db = new Database('default', '', $driver);
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDatabase($db);

        Expect::exception(DBALException::class);

        $dbal->addDatabase($db);
    }

    public function testAddDriver(): void
    {
        $driver = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('default', $driver);

        Assert::same($dbal->driver('default'), $driver);
    }

    public function testAddDriverException(): void
    {
        $driver = m::mock(DriverInterface::class);
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('default', $driver);

        Expect::exception(DBALException::class);

        $dbal->addDriver('default', $driver);
    }

    public function testDatabaseException(): void
    {
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        Expect::exception(DBALException::class);
        $dbal->database('default');
    }

    public function testDatabaseDrivers(): void
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('read', $read);
        $dbal->addDriver('write', $write);

        $db = $dbal->database('default');

        Assert::same($db->getDriver(Database::READ), $read);
        Assert::same($db->getDriver(Database::WRITE), $write);
    }

    public function testGetDrivers(): void
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));

        Assert::count($dbal->getDrivers(), 0);

        $dbal->addDriver('read', $read);
        $dbal->addDriver('write', $write);

        Assert::count($dbal->getDrivers(), 2);
    }

    public function testSetLogger(): void
    {
        $logger = m::mock(LoggerInterface::class);

        $driverWithoutLogger = m::mock(DriverInterface::class);

        $driverWithLogger = m::mock(DriverInterface::class, LoggerAwareInterface::class)
            ->shouldReceive('setLogger')->with($logger)->once()->andReturnNull()
            ->getMock();
        Assert::instanceOf($driverWithLogger, LoggerAwareInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));

        $dbal->addDriver('read', $driverWithoutLogger);
        $dbal->addDriver('write', $driverWithLogger);

        $dbal->setLogger($logger);

        m::close();
    }

    public function testGetDatabases(): void
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));

        $dbal->addDriver('read', $read);
        $dbal->addDriver('write', $write);

        Assert::count($dbal->getDatabases(), 1);
    }

    public function testGetDatabaseException(): void
    {
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));

        Expect::exception(DBALException::class);

        $dbal->database('other');
    }

    public function testGetDriverException(): void
    {
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));

        Expect::exception(DBALException::class);

        $dbal->driver('other');
    }

    public function testConfigured(): void
    {
        $dbal = new DatabaseManager(
            new DatabaseConfig(
                [
                    'default' => 'default',
                    'databases' => [
                        'default' => [
                            'driver' => 'default',
                        ],
                        'test' => [
                            'driver' => 'default',
                        ],
                    ],
                    'connections' => [
                        'default' => new SQLiteDriverConfig(),
                    ],
                ],
            ),
        );

        Assert::instanceOf($dbal->driver('default'), SQLiteDriver::class);
        Assert::instanceOf($dbal->database('default')->getDriver(), SQLiteDriver::class);
    }

    public function testCountDrivers(): void
    {
        $dbal = new DatabaseManager(
            new DatabaseConfig(
                [
                    'default' => 'default',
                    'databases' => [
                        'default' => [
                            'driver' => 'default',
                        ],
                        'test' => [
                            'driver' => 'default',
                        ],
                    ],
                    'connections' => [
                        'default' => new SQLiteDriverConfig(),
                    ],
                ],
            ),
        );

        Assert::count($dbal->getDrivers(), 1);
    }

    public function testCountDatabase(): void
    {
        $dbal = new DatabaseManager(
            new DatabaseConfig(
                [
                    'default' => 'default',
                    'databases' => [
                        'default' => [
                            'driver' => 'default',
                        ],
                        'test' => [
                            'driver' => 'default',
                        ],
                    ],
                    'connections' => [
                        'default' => new SQLiteDriverConfig(),
                    ],
                ],
            ),
        );

        Assert::count($dbal->getDatabases(), 2);

        $driver = m::mock(DriverInterface::class);
        $db = new Database('default2', '', $driver);
        $dbal->addDatabase($db);

        Assert::count($dbal->getDatabases(), 3);
    }

    public function testBadDriver(): void
    {
        $dbal = new DatabaseManager(
            new DatabaseConfig([
                'connections' => [
                    'default' => 'unknown',
                ],
            ]),
        );

        Expect::exception(\InvalidArgumentException::class);

        $dbal->driver('default');
    }
}
