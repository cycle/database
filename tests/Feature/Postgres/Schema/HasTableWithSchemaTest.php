<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Tests\Feature\Postgres\Helpers;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Lifecycle\AfterTest;
use Testo\Test;

final class HasTableWithSchemaTest extends DatabaseTestCase
{
    use Helpers;

    #[Test]
    public function testGetsTableNamesWithoutSchema(): void
    {
        $driver = $this->getDriver();
        $tables = $this->createTables($driver);

        Assert::true($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        Assert::true($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        Assert::true($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        Assert::false($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        Assert::true($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        Assert::false($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    #[Test]
    public function testGetsTableNamesWithoutSchemaWithDefinedDefaultSchema(): void
    {
        $driver = $this->getDriver(null, 'schema1');
        $tables = $this->createTables($driver);

        Assert::true($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        Assert::false($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        Assert::true($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        Assert::true($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        Assert::true($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        Assert::false($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    #[Test]
    public function testGetsTableNamesWithSpecifiedSchemaAsString(): void
    {
        $driver = $this->getDriver('schema1');
        $tables = $this->createTables($driver);

        Assert::true($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        Assert::false($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        Assert::true($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        Assert::true($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        Assert::true($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        Assert::false($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    #[Test]
    public function testGetsTableNamesWithSpecifiedSchemaAsArray(): void
    {
        $driver = $this->getDriver(['schema1', 'schema2']);
        $tables = $this->createTables($driver);

        Assert::true($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        Assert::false($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        Assert::true($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        Assert::true($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        Assert::true($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        Assert::false($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    #[Test]
    public function testGetsTableNamesWithSpecifiedSchemaAsArrayWithDefinedDefaultSchema(): void
    {
        $driver = $this->getDriver(['schema1', 'schema2'], 'schema2');
        $tables = $this->createTables($driver);

        Assert::true($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        Assert::false($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        Assert::true($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        Assert::false($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        Assert::true($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        Assert::true($driver->getSchemaHandler()->hasTable($tables['test_sh2']));
    }

    #[AfterTest]
    public function dropUserSchemaAfterTest(): void
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
