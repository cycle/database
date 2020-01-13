<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Core\Container;
use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\Database;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Driver\SQLite\SQLiteDriver;

class ManagerTest extends TestCase
{
    public const DEFAULT_OPTIONS = [
        'default'     => 'default',
        'databases'   => [
            'default' => [
                'prefix' => 'prefix_',
                'read'   => 'read',
                'write'  => 'write'
            ]
        ],
        'connections' => []
    ];

    public function testAddDatabase(): void
    {
        $driver = m::mock(DriverInterface::class);
        $db = new Database('default', '', $driver);


        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDatabase($db);

        $this->assertSame($db, $dbal->database('default'));
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     */
    public function testAddDatabaseException(): void
    {
        $driver = m::mock(DriverInterface::class);
        $db = new Database('default', '', $driver);


        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDatabase($db);
        $dbal->addDatabase($db);
    }

    public function testAddDriver(): void
    {
        $driver = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('default', $driver);

        $this->assertSame($driver, $dbal->driver('default'));
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     */
    public function testAddDriverException(): void
    {
        $driver = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('default', $driver);
        $dbal->addDriver('default', $driver);
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     */
    public function testDatabaseException(): void
    {
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
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

    public function testInjection(): void
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);
        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('read', $read);
        $dbal->addDriver('write', $write);
        $container = new Container();
        $container->bind(DatabaseManager::class, $dbal);
        $db = $container->get(Database::class);
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

    public function testGetDatabases(): void
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));

        $dbal->addDriver('read', $read);
        $dbal->addDriver('write', $write);

        $this->assertCount(1, $dbal->getDatabases());
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     */
    public function testGetDatabaseException(): void
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->database('other');
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     */
    public function testGetDriverException(): void
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DatabaseManager(new DatabaseConfig(self::DEFAULT_OPTIONS));
        $dbal->driver('other');
    }

    public function testConfigured(): void
    {
        $dbal = new DatabaseManager(
            new DatabaseConfig(
                [
                    'default'     => 'default',
                    'databases'   => [
                        'default' => [
                            'driver' => 'default'
                        ],
                        'test'    => [
                            'driver' => 'default'
                        ],
                    ],
                    'connections' => [
                        'default' => [
                            'driver'  => SQLiteDriver::class,
                            'options' => [
                                'connection' => 'sqlite::memory:',
                                'username'   => 'sqlite',
                                'password'   => ''
                            ]
                        ]
                    ]
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
                    'default'     => 'default',
                    'databases'   => [
                        'default' => [
                            'driver' => 'default'
                        ],
                        'test'    => [
                            'driver' => 'default'
                        ],
                    ],
                    'connections' => [
                        'default' => [
                            'driver'  => SQLiteDriver::class,
                            'options' => [
                                'connection' => 'sqlite::memory:',
                                'username'   => 'sqlite',
                                'password'   => ''
                            ]
                        ]
                    ]
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
                    'default'     => 'default',
                    'databases'   => [
                        'default' => [
                            'driver' => 'default'
                        ],
                        'test'    => [
                            'driver' => 'default'
                        ],
                    ],
                    'connections' => [
                        'default' => [
                            'driver'     => SQLiteDriver::class,
                            'connection' => 'sqlite::memory:',
                            'username'   => 'sqlite',
                            'password'   => ''
                        ]
                    ]
                ]
            )
        );

        $this->assertCount(2, $dbal->getDatabases());

        $driver = m::mock(DriverInterface::class);
        $db = new Database('default2', '', $driver);
        $dbal->addDatabase($db);

        $this->assertCount(3, $dbal->getDatabases());
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     */
    public function testBadDriver(): void
    {
        $dbal = new DatabaseManager(
            new DatabaseConfig(
                [

                    'connections' => [
                        'default' => new Container\Autowire('unknown')
                    ]
                ]
            )
        );

        $dbal->driver('default');
    }
}
