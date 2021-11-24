<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Database;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Exception\DBALException;

class DatabaseManagerTest extends TestCase
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

        $this->assertSame($db, $dbal->database('default'));
    }

    public function testAddDatabaseException(): void
    {
        $driver = m::mock(DriverInterface::class);
        $db = new Database('default', '', $driver);
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDatabase($db);

        $this->expectException(DBALException::class);

        $dbal->addDatabase($db);
    }

    public function testAddDriver(): void
    {
        $driver = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('default', $driver);

        $this->assertSame($driver, $dbal->driver('default'));
    }

    public function testAddDriverException(): void
    {
        $driver = m::mock(DriverInterface::class);
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('default', $driver);

        $this->expectException(DBALException::class);

        $dbal->addDriver('default', $driver);
    }

    public function testDatabaseException(): void
    {
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $this->expectException(DBALException::class);
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

        $this->assertSame($read, $db->getDriver(Database::READ));
        $this->assertSame($write, $db->getDriver(Database::WRITE));
    }

    public function testGetDrivers(): void
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));

        $this->assertCount(0, $dbal->getDrivers());

        $dbal->addDriver('read', $read);
        $dbal->addDriver('write', $write);

        $this->assertCount(2, $dbal->getDrivers());
    }

    public function testSetLogger(): void
    {
        $logger = m::mock(LoggerInterface::class);

        $driverWithoutLogger = m::mock(DriverInterface::class);

        $driverWithLogger = m::mock(DriverInterface::class, LoggerAwareInterface::class)
            ->shouldReceive('setLogger')->with($logger)->once()->andReturnNull()
            ->getMock();
        self::assertInstanceOf(LoggerAwareInterface::class, $driverWithLogger);

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

        $this->assertCount(1, $dbal->getDatabases());
    }

    public function testGetDatabaseException(): void
    {
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));

        $this->expectException(DBALException::class);

        $dbal->database('other');
    }

    public function testGetDriverException(): void
    {
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));

        $this->expectException(DBALException::class);

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
                ]
            )
        );

        $this->assertInstanceOf(SQLiteDriver::class, $dbal->driver('default'));
        $this->assertInstanceOf(SQLiteDriver::class, $dbal->database('default')->getDriver());
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
                ]
            )
        );

        $this->assertCount(1, $dbal->getDrivers());
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
                ]
            )
        );

        $this->assertCount(2, $dbal->getDatabases());

        $driver = m::mock(DriverInterface::class);
        $db = new Database('default2', '', $driver);
        $dbal->addDatabase($db);

        $this->assertCount(3, $dbal->getDatabases());
    }

    public function testBadDriver(): void
    {
        $dbal = new DatabaseManager(
            new DatabaseConfig([
                'connections' => [
                    'default' => 'unknown',
                ],
            ])
        );

        $this->expectException(\InvalidArgumentException::class);

        $dbal->driver('default');
    }
}
