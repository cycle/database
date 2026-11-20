<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\TableTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class TableTest extends CommonClass
{
    public const DRIVER = 'postgres';

    //Applause, PG
    #[\Testo\Test]
    public function testGetColumns(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

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

        \Testo\Assert::same($columns, $expected);
    }

    #[\Testo\Test]
    public function testSelectDistinct(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

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
        \Testo\Assert::count($data, 4);
    }

    #[\Testo\Test]
    public function testSelectDistinctOn(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

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
        \Testo\Assert::count($data, 3);
    }

    #[\Testo\Test]
    public function testDependencies(): void
    {
        $schema = $this->database->table('table2')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();

        $table = $this->database->table('table');

        \Testo\Assert::count($table->getDependencies(), 0);

        $schema = $table->getSchema();
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('table2', ['id']);
        $schema->save();

        \Testo\Assert::same($table->getDependencies(), ['public.table2']);
    }
}
