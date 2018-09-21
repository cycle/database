<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Core\Container;
use Spiral\Database\Config\DBALConfig;
use Spiral\Database\Database;
use Spiral\Database\DBAL;
use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Driver\SQLite\SQLiteDriver;

class DBALTest extends TestCase
{
    const DEFAULT_OPTIONS = [
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

    public function testAddDatabase()
    {
        $driver = m::mock(DriverInterface::class);
        $db = new Database('default', '', $driver);


        $dbal = new DBAL(new DBALConfig(self::DEFAULT_OPTIONS));
        $dbal->addDatabase($db);

        $this->assertSame($db, $dbal->database('default'));
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     */
    public function testAddDatabaseException()
    {
        $driver = m::mock(DriverInterface::class);
        $db = new Database('default', '', $driver);


        $dbal = new DBAL(new DBALConfig(self::DEFAULT_OPTIONS));
        $dbal->addDatabase($db);
        $dbal->addDatabase($db);
    }

    public function testAddDriver()
    {
        $driver = m::mock(DriverInterface::class);

        $dbal = new DBAL(new DBALConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('default', $driver);

        $this->assertSame($driver, $dbal->driver('default'));
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     */
    public function testAddDriverException()
    {
        $driver = m::mock(DriverInterface::class);

        $dbal = new DBAL(new DBALConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('default', $driver);
        $dbal->addDriver('default', $driver);
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     */
    public function testDatabaseException()
    {
        $dbal = new DBAL(new DBALConfig(self::DEFAULT_OPTIONS));
        $dbal->database('default');
    }

    public function testDatabaseDrivers()
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DBAL(new DBALConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('read', $read);
        $dbal->addDriver('write', $write);

        $db = $dbal->database('default');

        $this->assertSame($read, $db->getDriver(Database::READ));
        $this->assertSame($write, $db->getDriver(Database::WRITE));
    }

    public function testInjection()
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DBAL(new DBALConfig(self::DEFAULT_OPTIONS));
        $dbal->addDriver('read', $read);
        $dbal->addDriver('write', $write);

        $container = new Container();
        $container->bind(DBAL::class, $dbal);

        $db = $container->get(Database::class);

        $this->assertSame($read, $db->getDriver(Database::READ));
        $this->assertSame($write, $db->getDriver(Database::WRITE));
    }

    public function testGetDrivers()
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DBAL(new DBALConfig(self::DEFAULT_OPTIONS));

        $this->assertCount(0, $dbal->getDrivers());

        $dbal->addDriver('read', $read);
        $dbal->addDriver('write', $write);

        $this->assertCount(2, $dbal->getDrivers());
    }

    public function testGetDatabases()
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DBAL(new DBALConfig(self::DEFAULT_OPTIONS));

        $dbal->addDriver('read', $read);
        $dbal->addDriver('write', $write);

        $this->assertCount(1, $dbal->getDatabases());
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     */
    public function testGetDatabaseException()
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DBAL(new DBALConfig(self::DEFAULT_OPTIONS));
        $dbal->database('other');
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     */
    public function testGetDriverException()
    {
        $read = m::mock(DriverInterface::class);
        $write = m::mock(DriverInterface::class);

        $dbal = new DBAL(new DBALConfig(self::DEFAULT_OPTIONS));
        $dbal->driver('other');
    }

    public function testConfigured()
    {
        $dbal = new DBAL(new DBALConfig([
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
        ]));

        $this->assertInstanceOf(SQLiteDriver::class, $dbal->driver('default'));
        $this->assertInstanceOf(SQLiteDriver::class, $dbal->database('default')->getDriver());
    }

    public function testCountDrivers()
    {
        $dbal = new DBAL(new DBALConfig([
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
        ]));

        $this->assertCount(1, $dbal->getDrivers());

    }

    public function testCountDatabase()
    {
        $dbal = new DBAL(new DBALConfig([
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
        ]));

        $this->assertCount(2, $dbal->getDatabases());
    }
}