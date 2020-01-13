<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\tests\Cases\Database;

use PHPUnit\Framework\TestCase;
use Spiral\Core\Container\Autowire;
use Spiral\Database\Config\DatabaseConfig;

class ConfigTest extends TestCase
{
    public function testDefaultDatabase(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1'
            ]
        );

        $this->assertSame('database-1', $config->getDefaultDatabase());
    }

    public function testHasDatabase(): void
    {
        $config = new DatabaseConfig(
            [
                'default'   => 'database-1',
                'databases' => [
                    'test'  => [],
                    'test2' => [],
                ]
            ]
        );

        $this->assertTrue($config->hasDatabase('test'));
        $this->assertTrue($config->hasDatabase('test2'));
        $this->assertFalse($config->hasDatabase('database-1'));
    }

    /**
     * @expectedException \Spiral\Database\Exception\ConfigException
     */
    public function testDatabaseException(): void
    {
        $config = new DatabaseConfig(
            [
                'default'   => 'database-1',
                'databases' => [
                    'test'  => [],
                    'test2' => [],
                ]
            ]
        );
        $this->assertSame('test3', $config->getDatabase('test3'));
    }

    public function testDatabaseDriver(): void
    {
        $config = new DatabaseConfig(
            [
                'default'   => 'database-1',
                'databases' => [
                    'test'  => [
                        'connection' => 'abc'
                    ],
                    'test2' => [
                        'write' => 'bce'
                    ],
                ]
            ]
        );

        $this->assertSame('abc', $config->getDatabase('test')->getDriver());
        $this->assertSame('bce', $config->getDatabase('test2')->getDriver());
    }

    public function testDatabaseReadDriver(): void
    {
        $config = new DatabaseConfig(
            [
                'default'   => 'database-1',
                'databases' => [
                    'test'  => [
                        'connection'     => 'dce',
                        'readConnection' => 'abc'
                    ],
                    'test1' => [
                        'connection' => 'abc'
                    ],
                    'test2' => [
                        'write' => 'dce',
                        'read'  => 'bce'
                    ],
                ]
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
                'default'   => 'database-1',
                'databases' => [
                    'test'  => [
                        'tablePrefix' => 'abc',
                        'driver'      => 'test'
                    ],
                    'test2' => [
                        'tablePrefix' => 'bce',
                        'driver'      => 'test'
                    ],
                    'test3' => [
                        'driver' => 'test'
                    ]
                ]
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
                'default'   => 'database-1',
                'databases' => [
                    'test'  => [
                        'driver' => 'test'
                    ],
                    'test2' => [
                        'driver' => 'test'
                    ],
                ]
            ]
        );

        $this->assertSame(['test', 'test2'], array_keys($config->getDatabases()));
    }

    public function testAliases(): void
    {
        $config = new DatabaseConfig(
            [
                'default'   => 'database-1',
                'aliases'   => [
                    'test3' => 'test2',

                    //Recursive
                    'test6' => 'test5',
                    'test5' => 'test4',
                    'test4' => 'test'
                ],
                'databases' => [
                    'test'  => [],
                    'test2' => [],
                ]
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
                    'test'  => [],
                    'test2' => [],
                ]
            ]
        );

        $this->assertTrue($config->hasDriver('test'));
        $this->assertTrue($config->hasDriver('test2'));
        $this->assertFalse($config->hasDriver('database-1'));

        $config = new DatabaseConfig(
            [
                'drivers' => [
                    'test'  => [],
                    'test2' => [],
                ]
            ]
        );

        $this->assertTrue($config->hasDriver('test'));
        $this->assertTrue($config->hasDriver('test2'));
        $this->assertFalse($config->hasDriver('database-1'));
    }

    /**
     * @expectedException \Spiral\Database\Exception\ConfigException
     */
    public function testDriverException(): void
    {
        $config = new DatabaseConfig(
            [
                'default' => 'database-1',
            ]
        );

        $config->getDriver('test3');
    }

    public function testGetDriver(): void
    {
        $config = new DatabaseConfig(
            [
                'connections' => [
                    'test'  => [
                        'driver' => 'abc',
                        'option' => 'option'
                    ],
                    'test2' => [
                        'driver'  => 'bce',
                        'options' => [
                            'option'
                        ]
                    ],
                    'test3' => new Autowire('someDriver')
                ]
            ]
        );

        $this->assertInstanceOf(Autowire::class, $config->getDriver('test'));
        $this->assertInstanceOf(Autowire::class, $config->getDriver('test2'));
        $this->assertInstanceOf(Autowire::class, $config->getDriver('test3'));
    }

    public function testDriverNames(): void
    {
        $config = new DatabaseConfig(
            [
                'connections' => [
                    'test'  => [
                        'driver' => 'abc',
                        'option' => 'option'
                    ],
                    'test2' => [
                        'driver'  => 'bce',
                        'options' => [
                            'option'
                        ]
                    ],
                    'test3' => new Autowire('someDriver')
                ]
            ]
        );

        $this->assertSame(['test', 'test2', 'test3'], array_keys($config->getDrivers()));
    }
}
