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
use PHPUnit\Framework\TestCase;

class ConfigTest
{
    #[\Testo\Test]
    public function testMakeDatabaseWithoutConfig(): void
    {
        $config = new DatabaseConfig();
        \Testo\Assert::instanceOf($config, DatabaseConfig::class);
    }

    #[\Testo\Test]
    public function testDefaultDatabase(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1',
            ],
        );

        \Testo\Assert::same($config->getDefaultDatabase(), 'database-1');
    }

    #[\Testo\Test]
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

        \Testo\Assert::true($config->hasDatabase('test'));
        \Testo\Assert::true($config->hasDatabase('test2'));
        \Testo\Assert::false($config->hasDatabase('database-1'));
    }

    #[\Testo\Test]
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

        \Testo\Expect::exception(ConfigException::class);

        $config->getDatabase('test3');
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($config->getDatabase('test')->getDriver(), 'abc');
        \Testo\Assert::same($config->getDatabase('test2')->getDriver(), 'bce');
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($config->getDatabase('test')->getReadDriver(), 'abc');
        \Testo\Assert::same($config->getDatabase('test1')->getReadDriver(), null);
        \Testo\Assert::same($config->getDatabase('test2')->getReadDriver(), 'bce');
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($config->getDatabase('test')->getName(), 'test');
        \Testo\Assert::same($config->getDatabase('test')->getPrefix(), 'abc');
        \Testo\Assert::same($config->getDatabase('test2')->getPrefix(), 'bce');
        \Testo\Assert::same($config->getDatabase('test3')->getPrefix(), '');
    }

    #[\Testo\Test]
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

        \Testo\Assert::same(\array_keys($config->getDatabases()), ['test', 'test2']);
    }

    #[\Testo\Test]
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

        \Testo\Assert::true($config->hasDatabase('test'));
        \Testo\Assert::true($config->hasDatabase('test2'));
        \Testo\Assert::false($config->hasDatabase('test4'));
        \Testo\Assert::false($config->hasDatabase('test5'));
        \Testo\Assert::false($config->hasDatabase('test6'));

        \Testo\Assert::same($config->resolveAlias('test3'), 'test2');

        \Testo\Assert::same($config->resolveAlias('test6'), 'test');
        \Testo\Assert::same($config->resolveAlias('test5'), 'test');
        \Testo\Assert::same($config->resolveAlias('test4'), 'test');
    }

    #[\Testo\Test]
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

        \Testo\Assert::true($config->hasDriver('test'));
        \Testo\Assert::true($config->hasDriver('test2'));
        \Testo\Assert::false($config->hasDriver('database-1'));

        $config = new DatabaseConfig(
            [
                'drivers' => [
                    'test' => [],
                    'test2' => [],
                ],
            ],
        );

        \Testo\Assert::true($config->hasDriver('test'));
        \Testo\Assert::true($config->hasDriver('test2'));
        \Testo\Assert::false($config->hasDriver('database-1'));
    }

    #[\Testo\Test]
    public function testDriverException(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1',
            ],
        );

        \Testo\Expect::exception(ConfigException::class);

        $config->getDriver('test3');
    }

    #[\Testo\Test]
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

        \Testo\Assert::instanceOf($config->getDriver('test'), SQLiteDriver::class);
        \Testo\Assert::instanceOf($config->getDriver('test2'), MySQLDriver::class);
    }

    #[\Testo\Test]
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

        \Testo\Assert::same(\array_keys($config->getDrivers()), ['test', 'test2']);
    }
}
