<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\tests\Cases\Database;

use Interop\Container\ContainerInterface;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Core\FactoryInterface;
use Spiral\Database\Config\DatabasesConfig;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Driver\SQLite\SQLiteDriver;
use Spiral\Database\Database;

class ManagerTest extends TestCase
{
    const DEFAULT_OPTIONS = [
        'connection' => 'sqlite:' . __DIR__ . 'Drivers/SQLite/fixture/runtime.db',
        'username'   => 'sqlite',
        'password'   => '',
        'options'    => []
    ];

    public function testDefaultDatabase()
    {
        $config = m::mock(DatabasesConfig::class);
        $container = m::mock(ContainerInterface::class);
        $factory = m::mock(FactoryInterface::class);

        $db = m::mock(Database::class);

        $manager = new DatabaseManager($config, $container);

        $config->shouldReceive('defaultDatabase')->andReturn('default');
        $config->shouldReceive('resolveAlias')->with('default')->andReturn('default');
        $config->shouldReceive('hasDatabase')->with('default')->andReturn(true);

        $config->shouldReceive('databasePrefix')->with('default')->andReturn('prefix');
        $config->shouldReceive('databaseDriver')->with('default')->andReturn('driverName');

        $config->shouldReceive('hasDriver')->with('driverName')->andReturn(true);
        $config->shouldReceive('driverClass')->with('driverName')->andReturn(SQLiteDriver::class);

        $config->shouldReceive('driverOptions')->with('driverName')->andReturn(self::DEFAULT_OPTIONS);

        $container->shouldReceive('get', [FactoryInterface::class])->andReturn($factory);
        $factory->shouldReceive('make')->with(SQLiteDriver::class, [
            'name'    => 'driverName',
            'options' => self::DEFAULT_OPTIONS
        ])->andReturn($driver = new SQLiteDriver('driverName', self::DEFAULT_OPTIONS));

        $factory->shouldReceive('make')->with(Database::class, [
            'name'   => 'default',
            'prefix' => 'prefix',
            'driver' => $driver
        ])->andReturn($db);

        $this->assertSame($db, $manager->database());
    }

    public function testNamedDatabase()
    {
        $config = m::mock(DatabasesConfig::class);
        $container = m::mock(ContainerInterface::class);
        $factory = m::mock(FactoryInterface::class);

        $db = m::mock(Database::class);

        $manager = new DatabaseManager($config, $container);

        $config->shouldReceive('resolveAlias')->with('test')->andReturn('default');
        $config->shouldReceive('hasDatabase')->with('default')->andReturn(true);

        $config->shouldReceive('databasePrefix')->with('default')->andReturn('prefix');
        $config->shouldReceive('databaseDriver')->with('default')->andReturn('driverName');

        $config->shouldReceive('hasDriver')->with('driverName')->andReturn(true);
        $config->shouldReceive('driverClass')->with('driverName')->andReturn(SQLiteDriver::class);

        $config->shouldReceive('driverOptions')->with('driverName')->andReturn(self::DEFAULT_OPTIONS);

        $container->shouldReceive('get', [FactoryInterface::class])->andReturn($factory);
        $factory->shouldReceive('make')->with(SQLiteDriver::class, [
            'name'    => 'driverName',
            'options' => self::DEFAULT_OPTIONS
        ])->andReturn($driver = new SQLiteDriver('driverName', self::DEFAULT_OPTIONS));

        $factory->shouldReceive('make')->with(Database::class, [
            'name'   => 'default',
            'prefix' => 'prefix',
            'driver' => $driver
        ])->andReturn($db);

        $this->assertSame($db, $manager->database('test'));
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     * @expectedExceptionMessage Unable to create Database, no presets for 'test' found
     */
    public function testNoDatabase()
    {
        $config = m::mock(DatabasesConfig::class);
        $container = m::mock(ContainerInterface::class);
        $db = m::mock(Database::class);

        $manager = new DatabaseManager($config, $container);

        $config->shouldReceive('resolveAlias')->with('test')->andReturn('test');
        $config->shouldReceive('hasDatabase')->with('test')->andReturn(false);

        $this->assertSame($db, $manager->database('test'));
    }

    public function testCreateDriver()
    {
        $config = new DatabasesConfig([
            'default'     => 'default',
            'aliases'     => [],
            'databases'   => [],
            'connections' => [],
        ]);
        $manager = new DatabaseManager($config);

        $driver = $manager->makeDriver(
            'sqlite',
            SQLiteDriver::class,
            'sqlite:memory:',
            'sqlite'
        );
        $this->assertInstanceOf(SQLiteDriver::class, $driver);

        $this->assertSame($driver, $manager->driver('sqlite'));
        $this->assertSame([$driver], $manager->getDrivers());
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     */
    public function testCreateDriverTwice()
    {
        $config = new DatabasesConfig([
            'default'     => 'default',
            'aliases'     => [],
            'databases'   => [],
            'connections' => [],
        ]);
        $manager = new DatabaseManager($config);


        $driver = $manager->makeDriver(
            'sqlite',
            SQLiteDriver::class,
            'sqlite:memory:',
            'sqlite'
        );

        $driver = $manager->makeDriver(
            'sqlite',
            SQLiteDriver::class,
            'sqlite:memory:',
            'sqlite'
        );
    }

    public function testCreateDatabase()
    {
        $config = new DatabasesConfig([
            'default'     => 'default',
            'aliases'     => [],
            'databases'   => [],
            'connections' => [],
        ]);
        $manager = new DatabaseManager($config);

        $manager->makeDriver(
            'sqlite',
            SQLiteDriver::class,
            'sqlite:memory:',
            'sqlite'
        );


        $db = $manager->createDatabase('test', '', 'sqlite');
        $this->assertInstanceOf(Database::class, $db);

        $this->assertSame([$db], $manager->getDatabases());
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     */
    public function testCreateDatabaseTwice()
    {
        $config = new DatabasesConfig([
            'default'     => 'default',
            'aliases'     => [],
            'databases'   => [],
            'connections' => [],
        ]);
        $manager = new DatabaseManager($config);

        $manager->makeDriver(
            'sqlite',
            SQLiteDriver::class,
            'sqlite:memory:',
            'sqlite'
        );

        $db = $manager->createDatabase('test', '', 'sqlite');
        $db = $manager->createDatabase('test', '', 'sqlite');
    }

    public function testCreateDatabaseExplicit()
    {
        $config = new DatabasesConfig([
            'default'     => 'default',
            'aliases'     => [],
            'databases'   => [],
            'connections' => [],
        ]);
        $manager = new DatabaseManager($config);

        $driver = $manager->makeDriver(
            'sqlite',
            SQLiteDriver::class,
            'sqlite:memory:',
            'sqlite'
        );

        $db = $manager->createDatabase('test', '', $driver);
        $this->assertInstanceOf(Database::class, $db);
    }

    public function testInjectionTest()
    {
        $config = new DatabasesConfig([
            'default'     => 'default',
            'aliases'     => [],
            'databases'   => [],
            'connections' => [],
        ]);
        $manager = new DatabaseManager($config);

        $driver = $manager->makeDriver(
            'sqlite',
            SQLiteDriver::class,
            'sqlite:memory:',
            'sqlite'
        );

        $db = $manager->createDatabase('test', '', $driver);

        $this->assertSame(
            $db,
            $manager->createInjection(new \ReflectionClass(Database::class), 'test')
        );
    }

    /**
     * @expectedException \Spiral\Database\Exception\DBALException
     */
    public function testMissingDriver()
    {
        $config = new DatabasesConfig([
            'default'     => 'default',
            'aliases'     => [],
            'databases'   => [],
            'connections' => [],
        ]);
        $manager = new DatabaseManager($config);

        $driver = $manager->driver('invalid');
    }
}