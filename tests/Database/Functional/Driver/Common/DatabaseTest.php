<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common;

use Cycle\Database\Database;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\DriverInterface;
use Mockery as m;

abstract class DatabaseTest extends BaseTest
{
    #[\Testo\Test]
    public function testConnect(): void
    {
        $this->database->getDriver()->connect();
        \Testo\Assert::true($this->database->getDriver()->isConnected());
    }

    #[\Testo\Test]
    public function testGetName(): void
    {
        $db = $this->db();
        \Testo\Assert::same($db->getName(), 'default');

        $db = $this->db('test');
        \Testo\Assert::same($db->getName(), 'test');
    }

    #[\Testo\Test]
    public function testGetType(): void
    {
        $db = $this->db();
        \Testo\Assert::same($db->getType(), $db->getDriver()->getType());

        \Testo\Assert::same(\strtolower($db->getType()), \strtolower(static::DRIVER));
    }

    #[\Testo\Test]
    public function testReadWrite(): void
    {
        $wDriver = m::mock(DriverInterface::class);
        $rDriver = m::mock(DriverInterface::class);

        $db = new Database('default', '', $wDriver, $rDriver);

        \Testo\Assert::same($db->getDriver(), $wDriver);
        \Testo\Assert::same($db->getDriver(DatabaseInterface::WRITE), $wDriver);
        \Testo\Assert::same($db->getDriver(DatabaseInterface::READ), $rDriver);
    }

    #[\Testo\Test]
    public function testExecute(): void
    {
        $wDriver = m::mock(DriverInterface::class);
        $rDriver = m::mock(DriverInterface::class);

        $db = new Database('default', '', $wDriver, $rDriver);

        $wDriver->expects('execute')->with('test', ['param'])->andReturn(1);
        \Testo\Assert::same($db->execute('test', ['param']), 1);
    }

    #[\Testo\Test]
    public function testPrefix(): void
    {
        $db = $this->db();
        \Testo\Assert::false($db->hasTable('test'));
        \Testo\Assert::false($db->hasTable('prefix_test'));

        $schema = $db->test->getSchema();
        $schema->primary('id');
        $schema->save();

        $schema = $db->prefix_test->getSchema();
        $schema->primary('id');
        $schema->save();

        \Testo\Assert::true($db->hasTable('test'));
        \Testo\Assert::true($db->hasTable('prefix_test'));

        \Testo\Assert::count($db->getTables(), 2);

        $db = $db->withPrefix('pre');

        \Testo\Assert::false($db->hasTable('test'));
        \Testo\Assert::true($db->hasTable('fix_test'));

        \Testo\Assert::count($db->getTables(), 1);

        $db = $db->withPrefix('fix_');

        \Testo\Assert::true($db->hasTable('test'));

        $db = $db->withPrefix('', false);
        \Testo\Assert::true($db->hasTable('test'));
        \Testo\Assert::true($db->hasTable('prefix_test'));
        \Testo\Assert::count($db->getTables(), 2);
    }

    #[\Testo\Lifecycle\AfterTest]
    public function tearDown(): void
    {
        $this->dropDatabase($this->db());
    }
}
