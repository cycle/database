<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\Postgres;

use PHPUnit\Framework\TestCase;

class EraseTableTest extends TestCase
{
    use Helpers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropAllTables();
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

    public function testEraseTableWithSchema(): void
    {
        $driver = $this->getDriver(['schema2', 'schema1']);

        $table2 = $this->createTable($driver, 'table_to_erase');
        $table1 = $this->createTable($driver, 'schema1.table_to_erase');
        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema2.table_to_erase'));
        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema1.table_to_erase'));

        $driver->getQueryBuilder()->insertQuery('schema2.table_to_erase')->columns('id')->values([[2], [3]])->run();
        $driver->getQueryBuilder()->insertQuery('schema1.table_to_erase')->columns('id')->values([1])->run();
        $this->assertSame(2, $driver->getQueryBuilder()->selectQuery('', ['schema2.table_to_erase'])->count());
        $this->assertSame(1, $driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count());

        $driver->getSchemaHandler()->eraseTable($table2);
        $driver->getSchemaHandler()->eraseTable($table1);
        $this->assertSame(0, $driver->getQueryBuilder()->selectQuery('', ['schema1.table_to_erase'])->count());
        $this->assertSame(0, $driver->getQueryBuilder()->selectQuery('', ['schema2.table_to_erase'])->count());
    }
}
