<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\TableTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class TableTest extends CommonClass
{
    public const DRIVER = 'postgres';

    //Applause, PG
    public function testGetColumns(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $expected = [
            'id'    => 'primary',
            'name'  => 'text',
            'value' => 'integer'
        ];
        arsort($expected);

        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[$column->getName()] = $column->getAbstractType();
        }

        arsort($columns);

        $this->assertSame($expected, $columns);
    }

    public function testSelectDistinct(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['Anton', 20],
                ['Bob', 15],
                ['Charlie', 10]
            ]
        );

        $data = $table->select('name', 'value')->distinct('name')->fetchAll();
        $this->assertCount(4, $data);
    }

    public function testSelectDistinctOn(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['Anton', 20],
                ['Bob', 15],
                ['Charlie', 10]
            ]
        );

        $data = $table->select('name', 'value')->distinctOn('name')->fetchAll();
        $this->assertCount(3, $data);
    }
}
