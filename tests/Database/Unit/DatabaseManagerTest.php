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
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class DatabaseManagerTest
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

    #[\Testo\Test]
    public function testAddDatabase(): void
    {
        $driver = m::mock(DriverInterface::class);
        $db = new Database('default', '', $driver);


        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDatabase($db);

        \Testo\Assert::same($dbal->database('default'), $db);
    }

    #[\Testo\Test]
    public function testAddDatabaseException(): void
    {
        $driver = m::mock(DriverInterface::class);
        $db = new Database('default', '', $driver);
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDatabase($db);

        \Testo\Expect::exception(DBALException::class);

        $dbal->addDatabase($db);
    }

    #[\Testo\Test]
    public function testAddDriver(): void
    {
        $driver = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('default', $driver);

        \Testo\Assert::same($dbal->driver('default'), $driver);
    }

    #[\Testo\Test]
    public function testAddDriverException(): void
    {
        $driver = m::mock(DriverInterface::class);
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('default', $driver);

        \Testo\Expect::exception(DBALException::class);

        $dbal->addDriver('default', $driver);
    }

    #[\Testo\Test]
    public function testDatabaseException(): void
    {
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        \Testo\Expect::exception(DBALException::class);
        $dbal->database('default');
    }

    #[\Testo\Test]
    public function testDatabaseDrivers(): void
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('read', $read);
        $dbal->addDriver('write', $write);

        $db = $dbal->database('default');

        \Testo\Assert::same($db->getDriver(Database::READ), $read);
        \Testo\Assert::same($db->getDriver(Database::WRITE), $write);
    }

    #[\Testo\Test]
    public function testGetDrivers(): void
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));

        \Testo\Assert::count($dbal->getDrivers(), 0);

        $dbal->addDriver('read', $read);
        $dbal->addDriver('write', $write);

        \Testo\Assert::count($dbal->getDrivers(), 2);
    }

    #[\Testo\Test]
    public function testSetLogger(): void
    {
        $logger = m::mock(LoggerInterface::class);

        $driverWithoutLogger = m::mock(DriverInterface::class);

        $driverWithLogger = m::mock(DriverInterface::class, LoggerAwareInterface::class)
            ->shouldReceive('setLogger')->with($logger)->once()->andReturnNull()
            ->getMock();
        \Testo\Assert::instanceOf($driverWithLogger, LoggerAwareInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));

        $dbal->addDriver('read', $driverWithoutLogger);
        $dbal->addDriver('write', $driverWithLogger);

        $dbal->setLogger($logger);

        m::close();
    }

    #[\Testo\Test]
    public function testGetDatabases(): void
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));

        $dbal->addDriver('read', $read);
        $dbal->addDriver('write', $write);

        \Testo\Assert::count($dbal->getDatabases(), 1);
    }

    #[\Testo\Test]
    public function testGetDatabaseException(): void
    {
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));

        \Testo\Expect::exception(DBALException::class);

        $dbal->database('other');
    }

    #[\Testo\Test]
    public function testGetDriverException(): void
    {
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));

        \Testo\Expect::exception(DBALException::class);

        $dbal->driver('other');
    }

    #[\Testo\Test]
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

        \Testo\Assert::instanceOf($dbal->driver('default'), SQLiteDriver::class);
        \Testo\Assert::instanceOf($dbal->database('default')->getDriver(), SQLiteDriver::class);
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($dbal->getDrivers(), 1);
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($dbal->getDatabases(), 2);

        $driver = m::mock(DriverInterface::class);
        $db = new Database('default2', '', $driver);
        $dbal->addDatabase($db);

        \Testo\Assert::count($dbal->getDatabases(), 3);
    }

    #[\Testo\Test]
    public function testBadDriver(): void
    {
        $dbal = new DatabaseManager(
            new DatabaseConfig([
                'connections' => [
                    'default' => 'unknown',
                ],
            ]),
        );

        \Testo\Expect::exception(\InvalidArgumentException::class);

        $dbal->driver('default');
    }
}
