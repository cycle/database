<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common;

use Cycle\Database\Database;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\DriverInterface;
use Mockery as m;

abstract class DatabaseTest extends BaseTest
{
    public function tearDown(): void
    {
        $this->dropDatabase($this->db());
    }

    public function testConnect(): void
    {
        $this->database->getDriver()->connect();
        $this->assertTrue($this->database->getDriver()->isConnected());
    }

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
        $this->assertSame(
            $db->getDriver()->getType(),
            $db->getType()
        );

        $this->assertSame(strtolower(static::DRIVER), strtolower($db->getType()));
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
}
