<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

use Cycle\Database\Tests\Functional\Driver\Postgres\Helpers;
use Cycle\Database\Tests\Traits\Loggable;
use PHPUnit\Framework\TestCase;

class EraseTableTest extends TestCase
{
    use Helpers;
    use Loggable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSchemas();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->dropUserSchema();
    }

    public function testEraseTable(): void
    {
        $driver = $this->getDriver();

        $table = $this->createTable($driver, 'table_to_erase');
        $this->assertTrue($driver->getSchemaHandler()->hasTable('public.table_to_erase'));

        $driver->getQueryBuilder()->insertQuery('public.table_to_erase')->columns('id')->values([1])->run();
        $this->assertSame(1, $driver->getQueryBuilder()->selectQuery('', ['public.table_to_erase'])->count());

        $driver->getSchemaHandler()->eraseTable($table);
        $this->assertSame(0, $driver->getQueryBuilder()->selectQuery('', ['public.table_to_erase'])->count());
    }

    public function testEraseTableWithNotDefinedSchemaShouldNotThrowAnException(): void
    {
        $driver = $this->getDriver();

        $table = $this->createTable($driver, 'schema1.table_to_erase');
        $driver->getQueryBuilder()->insertQuery('schema1.table_to_erase')->columns('id')->values([1])->run();
        $this->assertSame(1, $driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count());

        $driver->getSchemaHandler()->eraseTable($table);
        $this->assertSame(0, $driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count());
    }

    public function testEraseTableWithSchema(): void
    {
        $driver = $this->getDriver(['schema2', 'schema1']);

        $table2 = $this->createTable($driver, 'table_to_erase');
        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema2.table_to_erase'));
        $this->assertFalse($driver->getSchemaHandler()->hasTable('schema1.table_to_erase'));
        $table1 = $this->createTable($driver, 'schema1.table_to_erase');
        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema1.table_to_erase'));

        $driver->getQueryBuilder()->insertQuery('schema2.table_to_erase')->columns('id')->values([[2], [3]])->run();
        $this->assertSame(2, $driver->getQueryBuilder()->selectQuery('', ['schema2.table_to_erase'])->count());
        $this->assertSame(0, $driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count());
        $driver->getQueryBuilder()->insertQuery('schema1.table_to_erase')->columns('id')->values([1])->run();
        $this->assertSame(1, $driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count());

        $driver->getSchemaHandler()->eraseTable($table2);
        $this->assertSame(0, $driver->getQueryBuilder()->selectQuery('', ['schema2.table_to_erase'])->count());
        $this->assertSame(1, $driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count());
        $driver->getSchemaHandler()->eraseTable($table1);
        $this->assertSame(0, $driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count());
    }

    public function testEraseTableWithSchemaForDefinedSchema(): void
    {
        $driver = $this->getDriver(['schema2', 'schema1'], 'schema1');

        $table = $this->createTable($driver, 'table_to_erase');
        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema1.table_to_erase'));

        $driver->getQueryBuilder()->insertQuery('table_to_erase')->columns('id')->values([[2], [3]])->run();
        $this->assertSame(2, $driver->getQueryBuilder()->selectQuery('', ['table_to_erase'])->count());

        $driver->getSchemaHandler()->eraseTable($table);
        $this->assertSame(0, $driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count());
    }

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

        $this->assertSame(2, (int) $result);
    }

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

        $this->assertSame(1, (int) $result);
    }
}
