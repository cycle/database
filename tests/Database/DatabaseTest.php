<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Tests;

use Mockery as m;
use Spiral\Database\Database;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\Driver\DriverInterface;

abstract class DatabaseTest extends BaseTest
{
    public function testGetName()
    {
        $db = $this->db();
        $this->assertSame('default', $db->getName());

        $db = $this->db('test');
        $this->assertSame('test', $db->getName());
    }

    public function testGetType()
    {
        $db = $this->db();
        $this->assertSame($this->getDriver()->getType(), $db->getType());
    }


    public function testPrefix()
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

    public function testReadWrite()
    {
        $wDriver = m::mock(DriverInterface::class);
        $rDriver = m::mock(DriverInterface::class);

        $db = new Database('default', '', $wDriver, $rDriver);

        $this->assertSame($wDriver, $db->getDriver());
        $this->assertSame($wDriver, $db->getDriver(DatabaseInterface::WRITE));
        $this->assertSame($rDriver, $db->getDriver(DatabaseInterface::READ));
    }

    public function testExecute()
    {
        $wDriver = m::mock(DriverInterface::class);
        $rDriver = m::mock(DriverInterface::class);

        $db = new Database('default', '', $wDriver, $rDriver);

        $wDriver->expects('execute')->with('test', ['param'])->andReturn(1);
        $this->assertSame(1, $db->execute("test", ['param']));
    }
}
