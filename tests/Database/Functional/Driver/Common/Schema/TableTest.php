<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Exception\StatementException;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Table;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;
use Cycle\Database\Tests\Stub\FooBarEnum;
use Cycle\Database\Tests\Stub\IntegerEnum;
use Cycle\Database\Tests\Stub\UntypedEnum;

abstract class TableTest extends BaseTest
{
    #[\Testo\Test]
    public function testGetSchema(): void
    {
        \Testo\Assert::array($this->database->getDriver()->__debugInfo());
        \Testo\Assert::instanceOf($this->database->table('table'), Table::class);
        \Testo\Assert::instanceOf($this->database->table('table')->getSchema(), AbstractTable::class);
    }

    #[\Testo\Test]
    public function testExistsAndEmpty(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->getFullName(), 'table');

        \Testo\Assert::true($table->exists());
        \Testo\Assert::same($table->count(), 0);

        \Testo\Assert::true($table->hasColumn('value'));
        \Testo\Assert::false($table->hasColumn('xx'));
    }

    #[\Testo\Test]
    public function testPrimaryKeys(): void
    {
        $table = $this->database->table('table');

        \Testo\Assert::same($table->getPrimaryKeys(), ['id']);
    }

    #[\Testo\Test]
    public function testHasIndex(): void
    {
        $table = $this->database->table('table');

        \Testo\Assert::false($table->hasIndex(['value']));

        $schema = $table->getSchema();
        $schema->index(['value']);
        $schema->save();

        \Testo\Assert::true($table->hasIndex(['value']));
    }

    #[\Testo\Test]
    public function testGetIndexes(): void
    {
        $table = $this->database->table('table');

        \Testo\Assert::count($table->getIndexes(), 0);

        $schema = $table->getSchema();
        $schema->index(['value']);
        $schema->save();

        \Testo\Assert::count($table->getIndexes(), 1);
    }

    #[\Testo\Test]
    public function testHasForeignKey(): void
    {
        $schema = $this->database->table('table2')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();

        $table = $this->database->table('table');

        \Testo\Assert::false($table->hasForeignKey(['external_id']));

        $schema = $table->getSchema();
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('table2', ['id']);
        $schema->save();

        \Testo\Assert::true($table->hasForeignKey(['external_id']));
    }

    #[\Testo\Test]
    public function testGetForeignKeys(): void
    {
        $schema = $this->database->table('table2')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();

        $table = $this->database->table('table');

        \Testo\Assert::count($table->getForeignKeys(), 0);

        $schema = $table->getSchema();
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('table2', ['id']);
        $schema->save();

        \Testo\Assert::count($table->getForeignKeys(), 1);
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

        \Testo\Assert::same($table->getDependencies(), ['table2']);
    }

    //see old versions of postgres
    #[\Testo\Test]
    public function testGetColumns(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[$column->getName()] = $column->getAbstractType();
        }

        \Testo\Assert::same($columns, [
            'id' => 'primary',
            'name' => 'text',
            'value' => 'integer',
        ]);
    }

    #[\Testo\Test]
    public function testInsertOneRow(): void
    {
        $table = $this->database->table('table');

        \Testo\Assert::same($table->count(), 0);

        $id = $table->insertOne(
            [
                'name' => 'Anton',
                'value' => 10,
            ],
        );

        \Testo\Assert::notNull($id);
        \Testo\Assert::same($id, 1);

        \Testo\Assert::same($table->count(), 1);

        \Testo\Assert::equals($table->fetchAll(), [
            ['id' => 1, 'name' => 'Anton', 'value' => 10],
        ]);
    }

    #[\Testo\Test]
    public function testInsertTypedEnum(): void
    {
        $table = $this->database->table('table');

        \Testo\Assert::same($table->count(), 0);

        $id = $table->insertOne(
            [
                'name' => FooBarEnum::FOO,
                'value' => IntegerEnum::HUNDRED,
            ],
        );

        \Testo\Assert::notNull($id);

        \Testo\Assert::equals($table->fetchAll(), [
            ['id' => 1, 'name' => FooBarEnum::FOO->value, 'value' => IntegerEnum::HUNDRED->value],
        ]);
    }

    #[\Testo\Test]
    public function testInsertTypelessEnum(): void
    {
        $table = $this->database->table('table');

        \Testo\Assert::same($table->count(), 0);

        \Testo\Expect::exception(StatementException::class);

        $table->insertOne(
            [
                'name' => 'Leo',
                'value' => UntypedEnum::FOO,
            ],
        );
    }

    #[\Testo\Test]
    public function testInsertOneRowAfterAnother(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $id = $table->insertOne(
            [
                'name' => 'Anton',
                'value' => 10,
            ],
        );

        \Testo\Assert::notNull($id);
        \Testo\Assert::same($id, 1);

        $id = $table->insertOne(
            [
                'name' => 'John',
                'value' => 20,
            ],
        );

        \Testo\Assert::notNull($id);
        \Testo\Assert::same($id, 2);

        \Testo\Assert::same($table->count(), 2);

        \Testo\Assert::equals($table->fetchAll(), [
            ['id' => 1, 'name' => 'Anton', 'value' => 10],
            ['id' => 2, 'name' => 'John', 'value' => 20],
        ]);
    }

    #[\Testo\Test]
    public function testInsertMultiple(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ],
        );

        \Testo\Assert::same($table->count(), 4);

        \Testo\Assert::equals($table->fetchAll(), [
            ['id' => 1, 'name' => 'Anton', 'value' => 10],
            ['id' => 2, 'name' => 'John', 'value' => 20],
            ['id' => 3, 'name' => 'Bob', 'value' => 30],
            ['id' => 4, 'name' => 'Charlie', 'value' => 40],
        ]);
    }

    #[\Testo\Test]
    public function testAggregationByPass(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ],
        );

        \Testo\Assert::same($table->count(), 4);
        \Testo\Assert::same((int) $table->sum('value'), 100);
    }

    #[\Testo\Test]
    public function testAggregationMinByPass(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ],
        );

        \Testo\Assert::same($table->count(), 4);
        \Testo\Assert::same((int) $table->min('value'), 10);
    }

    #[\Testo\Test]
    public function testAggregationMaxByPass(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ],
        );

        \Testo\Assert::same($table->count(), 4);
        \Testo\Assert::same((int) $table->max('value'), 40);
    }

    #[\Testo\Test]
    public function testAggregationAvgByPass(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ],
        );

        \Testo\Assert::same($table->count(), 4);
        \Testo\Assert::same((int) $table->avg('value'), 25);
    }

    #[\Testo\Test]
    public function testAggregationAvgByPassFloat(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        \Testo\Assert::same($table->count(), 4);
        \Testo\Assert::same((float) $table->avg('value'), 13.75);
    }

    #[\Testo\Test]
    public function testDeleteWithWhere(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        \Testo\Assert::same($table->count(), 4);
        \Testo\Assert::same($table->delete(['value' => 10])->run(), 2);
        \Testo\Assert::same($table->select()->where(['value' => 10])->count(), 0);
    }

    #[\Testo\Test]
    public function testUpdateWithWhere(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        \Testo\Assert::same($table->count(), 4);
        \Testo\Assert::same($table->update(['value' => 100])->where('value', 10)->run(), 2);
        \Testo\Assert::same($table->select()->where(['value' => 100])->count(), 2);
    }

    #[\Testo\Test]
    public function testUpdateWithFragment(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        \Testo\Assert::same($table->count(), 4);
        \Testo\Assert::same($table->update(['value' => new Expression('value * 2')])->where('value', 10)->run(), 2);

        \Testo\Assert::same($table->select()->where(['value' => 20])->count(), 3);
    }

    #[\Testo\Test]
    public function testTruncate(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        \Testo\Assert::same($table->count(), 4);
        $table->eraseData();
        \Testo\Assert::same($table->count(), 0);
    }

    #[\Testo\Test]
    public function testCountID(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        \Testo\Assert::same($table->select()->count('id'), 4);
    }

    #[\Testo\Test]
    public function testCountDistinct(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        \Testo\Assert::same($table->select()->count('id', true), 4);
        \Testo\Assert::same($table->select()->count('DISTINCT(id)'), 4);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');

        $schema->save();
    }
}
