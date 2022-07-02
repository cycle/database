<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Config\MySQL\TcpConnectionConfig;
use Cycle\Database\Driver\MySQL\MySQLDriver;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Exception\ConfigException;

class ConfigTest extends TestCase
{
     public function testMakeDatabaseWithoutConfig(): void
    {
        $config = new DatabaseConfig();
        $this->assertInstanceOf(DatabaseConfig::class, $config);
    }

    public function testDefaultDatabase(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1',
            ]
        );

        $this->assertSame('database-1', $config->getDefaultDatabase());
    }

    public function testHasDatabase(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1',
                'databases' => [
                    'test' => [],
                    'test2' => [],
                ],
            ]
        );

        $this->assertTrue($config->hasDatabase('test'));
        $this->assertTrue($config->hasDatabase('test2'));
        $this->assertFalse($config->hasDatabase('database-1'));
    }

    public function testDatabaseException(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1',
                'databases' => [
                    'test' => [],
                    'test2' => [],
                ],
            ]
        );

        $this->expectException(ConfigException::class);

        $config->getDatabase('test3');
    }

    public function testDatabaseDriver(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1',
                'databases' => [
                    'test' => [
                        'connection' => 'abc',
                    ],
                    'test2' => [
                        'write' => 'bce',
                    ],
                ],
            ]
        );

        $this->assertSame('abc', $config->getDatabase('test')->getDriver());
        $this->assertSame('bce', $config->getDatabase('test2')->getDriver());
    }

    public function testDatabaseReadDriver(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1',
                'databases' => [
                    'test' => [
                        'connection' => 'dce',
                        'readConnection' => 'abc',
                    ],
                    'test1' => [
                        'connection' => 'abc',
                    ],
                    'test2' => [
                        'write' => 'dce',
                        'read' => 'bce',
                    ],
                ],
            ]
        );

        $this->assertSame('abc', $config->getDatabase('test')->getReadDriver());
        $this->assertSame(null, $config->getDatabase('test1')->getReadDriver());
        $this->assertSame('bce', $config->getDatabase('test2')->getReadDriver());
    }

    public function testDatabasePrefix(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1',
                'databases' => [
                    'test' => [
                        'tablePrefix' => 'abc',
                        'driver' => 'test',
                    ],
                    'test2' => [
                        'tablePrefix' => 'bce',
                        'driver' => 'test',
                    ],
                    'test3' => [
                        'driver' => 'test',
                    ],
                ],
            ]
        );

        $this->assertSame('test', $config->getDatabase('test')->getName());
        $this->assertSame('abc', $config->getDatabase('test')->getPrefix());
        $this->assertSame('bce', $config->getDatabase('test2')->getPrefix());
        $this->assertSame('', $config->getDatabase('test3')->getPrefix());
    }

    public function testDatabaseNames(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1',
                'databases' => [
                    'test' => [
                        'driver' => 'test',
                    ],
                    'test2' => [
                        'driver' => 'test',
                    ],
                ],
            ]
        );

        $this->assertSame(['test', 'test2'], array_keys($config->getDatabases()));
    }

    public function testAliases(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1',
                'aliases' => [
                    'test3' => 'test2',

                    //Recursive
                    'test6' => 'test5',
                    'test5' => 'test4',
                    'test4' => 'test',
                ],
                'databases' => [
                    'test' => [],
                    'test2' => [],
                ],
            ]
        );

        $this->assertTrue($config->hasDatabase('test'));
        $this->assertTrue($config->hasDatabase('test2'));
        $this->assertFalse($config->hasDatabase('test4'));
        $this->assertFalse($config->hasDatabase('test5'));
        $this->assertFalse($config->hasDatabase('test6'));

        $this->assertSame('test2', $config->resolveAlias('test3'));

        $this->assertSame('test', $config->resolveAlias('test6'));
        $this->assertSame('test', $config->resolveAlias('test5'));
        $this->assertSame('test', $config->resolveAlias('test4'));
    }

    public function testHasDriver(): void
    {
        $config = new DatabaseConfig(
            [
                'connections' => [
                    'test' => [],
                    'test2' => [],
                ],
            ]
        );

        $this->assertTrue($config->hasDriver('test'));
        $this->assertTrue($config->hasDriver('test2'));
        $this->assertFalse($config->hasDriver('database-1'));

        $config = new DatabaseConfig(
            [
                'drivers' => [
                    'test' => [],
                    'test2' => [],
                ],
            ]
        );

        $this->assertTrue($config->hasDriver('test'));
        $this->assertTrue($config->hasDriver('test2'));
        $this->assertFalse($config->hasDriver('database-1'));
    }

    public function testDriverException(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1',
            ]
        );

        $this->expectException(ConfigException::class);

        $config->getDriver('test3');
    }

    public function testGetDriver(): void
    {
        $config = new DatabaseConfig(
            [
                'connections' => [
                    'test' => new SQLiteDriverConfig(),
                    'test2' => new MySQLDriverConfig(
                        connection: new TcpConnectionConfig(
                            database: 'spiral',
                            host: '127.0.0.1',
                            port: 13306,
                            user: 'root',
                            password: 'root',
                        )
                    ),
                ],
            ]
        );

        $this->assertInstanceOf(SQLiteDriver::class, $config->getDriver('test'));
        $this->assertInstanceOf(MySQLDriver::class, $config->getDriver('test2'));
    }

    public function testDriverNames(): void
    {
        $config = new DatabaseConfig(
            [
                'connections' => [
                    'test' => new SQLiteDriverConfig(),
                    'test2' => new MySQLDriverConfig(
                        connection: new TcpConnectionConfig(
                            database: 'spiral',
                            host: '127.0.0.1',
                            port: 13306,
                            user: 'root',
                            password: 'root',
                        )
                    ),
                ],
            ]
        );

        $this->assertSame(['test', 'test2'], array_keys($config->getDrivers()));
    }
}
