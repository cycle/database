<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Tests\Functional\Driver\Postgres\Helpers;
use PHPUnit\Framework\TestCase;

#[\Testo\Filter\Group('driver-postgres')]
class HasTableWithSchemaTest
{
    use Helpers;

    #[\Testo\Test]
    public function testGetsTableNamesWithoutSchema(): void
    {
        $driver = $this->getDriver();
        $tables = $this->createTables($driver);

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        \Testo\Assert::true($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        \Testo\Assert::false($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        \Testo\Assert::false($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    #[\Testo\Test]
    public function testGetsTableNamesWithoutSchemaWithDefinedDefaultSchema(): void
    {
        $driver = $this->getDriver(null, 'schema1');
        $tables = $this->createTables($driver);

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        \Testo\Assert::false($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        \Testo\Assert::true($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        \Testo\Assert::false($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    #[\Testo\Test]
    public function testGetsTableNamesWithSpecifiedSchemaAsString(): void
    {
        $driver = $this->getDriver('schema1');
        $tables = $this->createTables($driver);

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        \Testo\Assert::false($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        \Testo\Assert::true($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        \Testo\Assert::false($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    #[\Testo\Test]
    public function testGetsTableNamesWithSpecifiedSchemaAsArray(): void
    {
        $driver = $this->getDriver(['schema1', 'schema2']);
        $tables = $this->createTables($driver);

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        \Testo\Assert::false($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        \Testo\Assert::true($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        \Testo\Assert::false($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    #[\Testo\Test]
    public function testGetsTableNamesWithSpecifiedSchemaAsArrayWithDefinedDefaultSchema(): void
    {
        $driver = $this->getDriver(['schema1', 'schema2'], 'schema2');
        $tables = $this->createTables($driver);

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        \Testo\Assert::false($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        \Testo\Assert::false($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        \Testo\Assert::true($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        \Testo\Assert::true($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    #[\Testo\Lifecycle\AfterTest]
    protected function tearDown(): void
    {
        $this->dropUserSchema();
    }

    protected function createTables(DriverInterface $driver): array
    {
        $tables = [];
        $time = \time();

        foreach (['public.test_pb', 'schema1.test_sh1', 'schema2.test_sh2'] as $table) {
            $driver->query('CREATE TABLE ' . $table . '_' . $time . '()');

            $table = \explode('.', $table)[1];
            $tables[$table] = $table . '_' . $time;
        }

        return $tables;
    }
}
