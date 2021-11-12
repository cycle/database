<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

use Cycle\Database\Driver\DriverInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group driver-postgres
 */
class HasTableWithSchemaTest extends TestCase
{
    use Helpers;

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->dropUserSchema();
    }

    public function testGetsTableNamesWithoutSchema(): void
    {
        $driver = $this->getDriver();
        $tables = $this->createTables($driver);

        $this->assertTrue($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        $this->assertTrue($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    public function testGetsTableNamesWithoutSchemaWithDefinedDefaultSchema(): void
    {
        $driver = $this->getDriver(null, 'schema1');
        $tables = $this->createTables($driver);

        $this->assertTrue($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        $this->assertTrue($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    public function testGetsTableNamesWithSpecifiedSchemaAsString(): void
    {
        $driver = $this->getDriver('schema1');
        $tables = $this->createTables($driver);

        $this->assertTrue($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        $this->assertTrue($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    public function testGetsTableNamesWithSpecifiedSchemaAsArray(): void
    {
        $driver = $this->getDriver(['schema1', 'schema2']);
        $tables = $this->createTables($driver);

        $this->assertTrue($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        $this->assertTrue($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    public function testGetsTableNamesWithSpecifiedSchemaAsArrayWithDefinedDefaultSchema(): void
    {
        $driver = $this->getDriver(['schema1', 'schema2'], 'schema2');
        $tables = $this->createTables($driver);

        $this->assertTrue($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        $this->assertTrue($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    protected function createTables(DriverInterface $driver): array
    {
        $tables = [];
        $time = time();

        foreach (['public.test_pb', 'schema1.test_sh1', 'schema2.test_sh2'] as $table) {
            $driver->query('CREATE TABLE ' . $table . '_' . $time . '()');

            $table = explode('.', $table)[1];
            $tables[$table] = $table . '_' . $time;
        }

        return $tables;
    }
}
