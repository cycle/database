<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Database\Tests\Feature\Postgres\Helpers;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Lifecycle\AfterTest;
use Testo\Test;

final class EraseTableTest extends DatabaseTestCase
{
    use Helpers;

    #[Test]
    public function testEraseTable(): void
    {
        $driver = $this->getDriver();

        $table = $this->createTable($driver, 'table_to_erase');
        Assert::true($driver->getSchemaHandler()->hasTable('public.table_to_erase'));

        $driver->getQueryBuilder()->insertQuery('public.table_to_erase')->columns('id')->values([1])->run();
        Assert::same($driver->getQueryBuilder()->selectQuery('', ['public.table_to_erase'])->count(), 1);

        $driver->getSchemaHandler()->eraseTable($table);
        Assert::same($driver->getQueryBuilder()->selectQuery('', ['public.table_to_erase'])->count(), 0);
    }

    #[Test]
    public function testEraseTableWithNotDefinedSchemaShouldNotThrowAnException(): void
    {
        $driver = $this->getDriver();

        $table = $this->createTable($driver, 'schema1.table_to_erase');
        $driver->getQueryBuilder()->insertQuery('schema1.table_to_erase')->columns('id')->values([1])->run();
        Assert::same($driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count(), 1);

        $driver->getSchemaHandler()->eraseTable($table);
        Assert::same($driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count(), 0);
    }

    #[Test]
    public function testEraseTableWithSchema(): void
    {
        $driver = $this->getDriver(['schema2', 'schema1']);

        $table2 = $this->createTable($driver, 'table_to_erase');
        Assert::true($driver->getSchemaHandler()->hasTable('schema2.table_to_erase'));
        Assert::false($driver->getSchemaHandler()->hasTable('schema1.table_to_erase'));
        $table1 = $this->createTable($driver, 'schema1.table_to_erase');
        Assert::true($driver->getSchemaHandler()->hasTable('schema1.table_to_erase'));

        $driver->getQueryBuilder()->insertQuery('schema2.table_to_erase')->columns('id')->values([[2], [3]])->run();
        Assert::same($driver->getQueryBuilder()->selectQuery('', ['schema2.table_to_erase'])->count(), 2);
        Assert::same($driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count(), 0);
        $driver->getQueryBuilder()->insertQuery('schema1.table_to_erase')->columns('id')->values([1])->run();
        Assert::same($driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count(), 1);

        $driver->getSchemaHandler()->eraseTable($table2);
        Assert::same($driver->getQueryBuilder()->selectQuery('', ['schema2.table_to_erase'])->count(), 0);
        Assert::same($driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count(), 1);
        $driver->getSchemaHandler()->eraseTable($table1);
        Assert::same($driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count(), 0);
    }

    #[Test]
    public function testEraseTableWithSchemaForDefinedSchema(): void
    {
        $driver = $this->getDriver(['schema2', 'schema1'], 'schema1');

        $table = $this->createTable($driver, 'table_to_erase');
        Assert::true($driver->getSchemaHandler()->hasTable('schema1.table_to_erase'));

        $driver->getQueryBuilder()->insertQuery('table_to_erase')->columns('id')->values([[2], [3]])->run();
        Assert::same($driver->getQueryBuilder()->selectQuery('', ['table_to_erase'])->count(), 2);

        $driver->getSchemaHandler()->eraseTable($table);
        Assert::same($driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count(), 0);
    }

    #[Test]
    public function testEraseTableWithoutRestartIdentity(): void
    {
        $driver = $this->getDriver();

        $table = $this->createTable($driver, 'table_to_erase');
        $table->primary('id');
        $table->string('name');
        $table->save();

        $driver
            ->getQueryBuilder()
            ->insertQuery('', 'table_to_erase')
            ->columns('name')->values(['foo'])
            ->run();

        $driver->getSchemaHandler()->eraseTable($table);

        $result = $driver
            ->getQueryBuilder()
            ->insertQuery('', 'table_to_erase')
            ->columns('name')->values(['foo'])
            ->run();

        Assert::same((int) $result, 2);
    }

    #[Test]
    public function testEraseTableWithRestartIdentity(): void
    {
        $driver = $this->getDriver();

        $table = $this->createTable($driver, 'table_to_erase');
        $table->primary('id');
        $table->string('name');
        $table->save();

        $driver
            ->getQueryBuilder()
            ->insertQuery('', 'table_to_erase')
            ->columns('name')->values(['foo'])
            ->run();

        $driver->getSchemaHandler()->eraseTable($table, true);

        $result = $driver
            ->getQueryBuilder()
            ->insertQuery('', 'table_to_erase')
            ->columns('name')->values(['foo'])
            ->run();

        Assert::same((int) $result, 1);
    }

    #[AfterTest]
    public function dropUserSchemaAfterTest(): void
    {
        $this->dropUserSchema();
    }
}
