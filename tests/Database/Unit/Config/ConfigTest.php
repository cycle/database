<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\MySQL\TcpConnectionConfig;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Driver\MySQL\MySQLDriver;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Exception\ConfigException;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class ConfigTest
{
    public function testMakeDatabaseWithoutConfig(): void
    {
        $config = new DatabaseConfig();
        Assert::instanceOf($config, DatabaseConfig::class);
    }

    public function testDefaultDatabase(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1',
            ],
        );

        Assert::same($config->getDefaultDatabase(), 'database-1');
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
            ],
        );

        Assert::true($config->hasDatabase('test'));
        Assert::true($config->hasDatabase('test2'));
        Assert::false($config->hasDatabase('database-1'));
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
            ],
        );

        Expect::exception(ConfigException::class);

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
            ],
        );

        Assert::same($config->getDatabase('test')->getDriver(), 'abc');
        Assert::same($config->getDatabase('test2')->getDriver(), 'bce');
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
            ],
        );

        Assert::same($config->getDatabase('test')->getReadDriver(), 'abc');
        Assert::same($config->getDatabase('test1')->getReadDriver(), null);
        Assert::same($config->getDatabase('test2')->getReadDriver(), 'bce');
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
            ],
        );

        Assert::same($config->getDatabase('test')->getName(), 'test');
        Assert::same($config->getDatabase('test')->getPrefix(), 'abc');
        Assert::same($config->getDatabase('test2')->getPrefix(), 'bce');
        Assert::same($config->getDatabase('test3')->getPrefix(), '');
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
            ],
        );

        Assert::same(\array_keys($config->getDatabases()), ['test', 'test2']);
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
            ],
        );

        Assert::true($config->hasDatabase('test'));
        Assert::true($config->hasDatabase('test2'));
        Assert::false($config->hasDatabase('test4'));
        Assert::false($config->hasDatabase('test5'));
        Assert::false($config->hasDatabase('test6'));

        Assert::same($config->resolveAlias('test3'), 'test2');

        Assert::same($config->resolveAlias('test6'), 'test');
        Assert::same($config->resolveAlias('test5'), 'test');
        Assert::same($config->resolveAlias('test4'), 'test');
    }

    public function testHasDriver(): void
    {
        $config = new DatabaseConfig(
            [
                'connections' => [
                    'test' => [],
                    'test2' => [],
                ],
            ],
        );

        Assert::true($config->hasDriver('test'));
        Assert::true($config->hasDriver('test2'));
        Assert::false($config->hasDriver('database-1'));

        $config = new DatabaseConfig(
            [
                'drivers' => [
                    'test' => [],
                    'test2' => [],
                ],
            ],
        );

        Assert::true($config->hasDriver('test'));
        Assert::true($config->hasDriver('test2'));
        Assert::false($config->hasDriver('database-1'));
    }

    public function testDriverException(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1',
            ],
        );

        Expect::exception(ConfigException::class);

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
                        ),
                    ),
                ],
            ],
        );

        Assert::instanceOf($config->getDriver('test'), SQLiteDriver::class);
        Assert::instanceOf($config->getDriver('test2'), MySQLDriver::class);
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
                        ),
                    ),
                ],
            ],
        );

        Assert::same(\array_keys($config->getDrivers()), ['test', 'test2']);
    }
}
