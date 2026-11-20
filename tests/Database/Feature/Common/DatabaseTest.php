<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common;

use Cycle\Database\Database;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Testing\Database\DatabaseTestCase;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

final class DatabaseTest extends DatabaseTestCase
{
    #[Test]
    public function testConnect(): void
    {
        $this->database->getDriver()->connect();
        Assert::true($this->database->getDriver()->isConnected());
    }

    #[Test]
    public function testGetName(): void
    {
        $db = $this->db();
        Assert::same($db->getName(), 'default');

        $db = $this->db('test');
        Assert::same($db->getName(), 'test');
    }

    #[Test]
    public function testGetType(): void
    {
        $db = $this->db();
        Assert::same($db->getType(), $db->getDriver()->getType());

        Assert::same(\strtolower($db->getType()), $this->driver->value);
    }

    #[Test]
    public function testReadWrite(): void
    {
        $wDriver = m::mock(DriverInterface::class);
        $rDriver = m::mock(DriverInterface::class);

        $db = new Database('default', '', $wDriver, $rDriver);

        Assert::same($db->getDriver(), $wDriver);
        Assert::same($db->getDriver(DatabaseInterface::WRITE), $wDriver);
        Assert::same($db->getDriver(DatabaseInterface::READ), $rDriver);
    }

    #[Test]
    public function testExecute(): void
    {
        $wDriver = m::mock(DriverInterface::class);
        $rDriver = m::mock(DriverInterface::class);

        $db = new Database('default', '', $wDriver, $rDriver);

        $wDriver->expects('execute')->with('test', ['param'])->andReturn(1);
        Assert::same($db->execute('test', ['param']), 1);
    }

    #[Test]
    public function testPrefix(): void
    {
        $db = $this->db();
        Assert::false($db->hasTable('test'));
        Assert::false($db->hasTable('prefix_test'));

        $schema = $db->test->getSchema();
        $schema->primary('id');
        $schema->save();

        $schema = $db->prefix_test->getSchema();
        $schema->primary('id');
        $schema->save();

        Assert::true($db->hasTable('test'));
        Assert::true($db->hasTable('prefix_test'));

        Assert::count($db->getTables(), 2);

        $db = $db->withPrefix('pre');

        Assert::false($db->hasTable('test'));
        Assert::true($db->hasTable('fix_test'));

        Assert::count($db->getTables(), 1);

        $db = $db->withPrefix('fix_');

        Assert::true($db->hasTable('test'));

        $db = $db->withPrefix('', false);
        Assert::true($db->hasTable('test'));
        Assert::true($db->hasTable('prefix_test'));
        Assert::count($db->getTables(), 2);
    }
}
