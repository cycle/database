<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use Mockery as m;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Spiral\Database\Database;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\Driver\DriverInterface;

abstract class DatabaseTest extends BaseTest
{
    public function testGetName(): void
    {
        $db = $this->db();
        $this->assertSame('default', $db->getName());

        $db = $this->db('test');
        $this->assertSame('test', $db->getName());
    }

    public function testGetType(): void
    {
        $db = $this->db();
        $this->assertSame($this->getDriver()->getType(), $db->getType());
    }

    public function testDriverVerbosity(): void
    {
        $driver = $this->getDriver();
        $driver->setLogger($l = new class() implements LoggerInterface {
            use LoggerTrait;

            public $records = [];

            public function log($level, $message, array $context = []): void
            {
                $this->records = func_get_args();
            }
        });

        $driver->getSchema('test');

        $driver->setProfiling(true);

        $driver->getSchema('test');
        $this->assertNotEmpty($l->records);
        $count = count($l->records);

        $driver->setProfiling(false);
        $driver->getSchema('test');
        $this->assertSame($count, count($l->records));
    }

    public function testPrefix(): void
    {
        $db = $this->db();
        $this->assertFalse($db->hasTable('test'));
        $this->assertFalse($db->hasTable('prefix_test'));

        $schema = $db->test->getSchema();
        $schema->primary('id');
        $schema->save();

        $schema = $db->prefix_test->getSchema();
        $schema->primary('id');
        $schema->save();

        $this->assertTrue($db->hasTable('test'));
        $this->assertTrue($db->hasTable('prefix_test'));

        $this->assertCount(2, $db->getTables());

        $db = $db->withPrefix('pre');

        $this->assertFalse($db->hasTable('test'));
        $this->assertTrue($db->hasTable('fix_test'));

        $this->assertCount(1, $db->getTables());

        $db = $db->withPrefix('fix_');

        $this->assertTrue($db->hasTable('test'));

        $db = $db->withPrefix('', false);
        $this->assertTrue($db->hasTable('test'));
        $this->assertTrue($db->hasTable('prefix_test'));
        $this->assertCount(2, $db->getTables());
    }

    public function testReadWrite(): void
    {
        $wDriver = m::mock(DriverInterface::class);
        $rDriver = m::mock(DriverInterface::class);

        $db = new Database('default', '', $wDriver, $rDriver);

        $this->assertSame($wDriver, $db->getDriver());
        $this->assertSame($wDriver, $db->getDriver(DatabaseInterface::WRITE));
        $this->assertSame($rDriver, $db->getDriver(DatabaseInterface::READ));
    }

    public function testExecute(): void
    {
        $wDriver = m::mock(DriverInterface::class);
        $rDriver = m::mock(DriverInterface::class);

        $db = new Database('default', '', $wDriver, $rDriver);

        $wDriver->expects('execute')->with('test', ['param'])->andReturn(1);
        $this->assertSame(1, $db->execute('test', ['param']));
    }
}
