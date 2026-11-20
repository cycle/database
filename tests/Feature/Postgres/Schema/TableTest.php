<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Database\Tests\Feature\Common\Schema\TableTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::Postgres)]
final class TableTest extends CommonClass
{
    //Applause, PG
    #[Test]
    public function testGetColumns(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $expected = [
            'id' => 'primary',
            'name' => 'text',
            'value' => 'integer',
        ];
        \arsort($expected);

        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[$column->getName()] = $column->getAbstractType();
        }

        \arsort($columns);

        Assert::same($columns, $expected);
    }

    #[Test]
    public function testSelectDistinct(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['Anton', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        $data = $table->select('name', 'value')->distinct('name')->fetchAll();
        Assert::count($data, 4);
    }

    #[Test]
    public function testSelectDistinctOn(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['Anton', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        $data = $table->select('name', 'value')->distinctOn('name')->fetchAll();
        Assert::count($data, 3);
    }

    #[Test]
    public function testDependencies(): void
    {
        $schema = $this->database->table('table2')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();

        $table = $this->database->table('table');

        Assert::count($table->getDependencies(), 0);

        $schema = $table->getSchema();
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('table2', ['id']);
        $schema->save();

        Assert::same($table->getDependencies(), ['public.table2']);
    }
}
