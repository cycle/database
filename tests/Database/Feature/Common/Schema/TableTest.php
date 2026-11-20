<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Schema;

use Cycle\Database\Exception\StatementException;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Table;
use Cycle\Database\Tests\Fixtures\FooBarEnum;
use Cycle\Database\Tests\Fixtures\IntegerEnum;
use Cycle\Database\Tests\Fixtures\UntypedEnum;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[OnlyDriver(Driver::SQLite)]
class TableTest extends DatabaseTestCase
{
    #[Test]
    public function testGetSchema(): void
    {
        Assert::array($this->database->getDriver()->__debugInfo());
        Assert::instanceOf($this->database->table('table'), Table::class);
        Assert::instanceOf($this->database->table('table')->getSchema(), AbstractTable::class);
    }

    #[Test]
    public function testExistsAndEmpty(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->getFullName(), 'table');

        Assert::true($table->exists());
        Assert::same($table->count(), 0);

        Assert::true($table->hasColumn('value'));
        Assert::false($table->hasColumn('xx'));
    }

    #[Test]
    public function testPrimaryKeys(): void
    {
        $table = $this->database->table('table');

        Assert::same($table->getPrimaryKeys(), ['id']);
    }

    #[Test]
    public function testHasIndex(): void
    {
        $table = $this->database->table('table');

        Assert::false($table->hasIndex(['value']));

        $schema = $table->getSchema();
        $schema->index(['value']);
        $schema->save();

        Assert::true($table->hasIndex(['value']));
    }

    #[Test]
    public function testGetIndexes(): void
    {
        $table = $this->database->table('table');

        Assert::count($table->getIndexes(), 0);

        $schema = $table->getSchema();
        $schema->index(['value']);
        $schema->save();

        Assert::count($table->getIndexes(), 1);
    }

    #[Test]
    public function testHasForeignKey(): void
    {
        $schema = $this->database->table('table2')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();

        $table = $this->database->table('table');

        Assert::false($table->hasForeignKey(['external_id']));

        $schema = $table->getSchema();
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('table2', ['id']);
        $schema->save();

        Assert::true($table->hasForeignKey(['external_id']));
    }

    #[Test]
    public function testGetForeignKeys(): void
    {
        $schema = $this->database->table('table2')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();

        $table = $this->database->table('table');

        Assert::count($table->getForeignKeys(), 0);

        $schema = $table->getSchema();
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('table2', ['id']);
        $schema->save();

        Assert::count($table->getForeignKeys(), 1);
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

        Assert::same($table->getDependencies(), ['table2']);
    }

    //see old versions of postgres
    #[Test]
    public function testGetColumns(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[$column->getName()] = $column->getAbstractType();
        }

        Assert::same($columns, [
            'id' => 'primary',
            'name' => 'text',
            'value' => 'integer',
        ]);
    }

    #[Test]
    public function testInsertOneRow(): void
    {
        $table = $this->database->table('table');

        Assert::same($table->count(), 0);

        $id = $table->insertOne(
            [
                'name' => 'Anton',
                'value' => 10,
            ],
        );

        Assert::notNull($id);
        Assert::same($id, 1);

        Assert::same($table->count(), 1);

        Assert::equals($table->fetchAll(), [
            ['id' => 1, 'name' => 'Anton', 'value' => 10],
        ]);
    }

    #[Test]
    public function testInsertTypedEnum(): void
    {
        $table = $this->database->table('table');

        Assert::same($table->count(), 0);

        $id = $table->insertOne(
            [
                'name' => FooBarEnum::FOO,
                'value' => IntegerEnum::HUNDRED,
            ],
        );

        Assert::notNull($id);

        Assert::equals($table->fetchAll(), [
            ['id' => 1, 'name' => FooBarEnum::FOO->value, 'value' => IntegerEnum::HUNDRED->value],
        ]);
    }

    #[Test]
    public function testInsertTypelessEnum(): void
    {
        $table = $this->database->table('table');

        Assert::same($table->count(), 0);

        Expect::exception(StatementException::class);

        $table->insertOne(
            [
                'name' => 'Leo',
                'value' => UntypedEnum::FOO,
            ],
        );
    }

    #[Test]
    public function testInsertOneRowAfterAnother(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $id = $table->insertOne(
            [
                'name' => 'Anton',
                'value' => 10,
            ],
        );

        Assert::notNull($id);
        Assert::same($id, 1);

        $id = $table->insertOne(
            [
                'name' => 'John',
                'value' => 20,
            ],
        );

        Assert::notNull($id);
        Assert::same($id, 2);

        Assert::same($table->count(), 2);

        Assert::equals($table->fetchAll(), [
            ['id' => 1, 'name' => 'Anton', 'value' => 10],
            ['id' => 2, 'name' => 'John', 'value' => 20],
        ]);
    }

    #[Test]
    public function testInsertMultiple(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ],
        );

        Assert::same($table->count(), 4);

        Assert::equals($table->fetchAll(), [
            ['id' => 1, 'name' => 'Anton', 'value' => 10],
            ['id' => 2, 'name' => 'John', 'value' => 20],
            ['id' => 3, 'name' => 'Bob', 'value' => 30],
            ['id' => 4, 'name' => 'Charlie', 'value' => 40],
        ]);
    }

    #[Test]
    public function testAggregationByPass(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ],
        );

        Assert::same($table->count(), 4);
        Assert::same((int) $table->sum('value'), 100);
    }

    #[Test]
    public function testAggregationMinByPass(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ],
        );

        Assert::same($table->count(), 4);
        Assert::same((int) $table->min('value'), 10);
    }

    #[Test]
    public function testAggregationMaxByPass(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ],
        );

        Assert::same($table->count(), 4);
        Assert::same((int) $table->max('value'), 40);
    }

    #[Test]
    public function testAggregationAvgByPass(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ],
        );

        Assert::same($table->count(), 4);
        Assert::same((int) $table->avg('value'), 25);
    }

    #[Test]
    public function testAggregationAvgByPassFloat(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        Assert::same($table->count(), 4);
        Assert::same((float) $table->avg('value'), 13.75);
    }

    #[Test]
    public function testDeleteWithWhere(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        Assert::same($table->count(), 4);
        Assert::same($table->delete(['value' => 10])->run(), 2);
        Assert::same($table->select()->where(['value' => 10])->count(), 0);
    }

    #[Test]
    public function testUpdateWithWhere(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        Assert::same($table->count(), 4);
        Assert::same($table->update(['value' => 100])->where('value', 10)->run(), 2);
        Assert::same($table->select()->where(['value' => 100])->count(), 2);
    }

    #[Test]
    public function testUpdateWithFragment(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        Assert::same($table->count(), 4);
        Assert::same($table->update(['value' => new Expression('value * 2')])->where('value', 10)->run(), 2);

        Assert::same($table->select()->where(['value' => 20])->count(), 3);
    }

    #[Test]
    public function testTruncate(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        Assert::same($table->count(), 4);
        $table->eraseData();
        Assert::same($table->count(), 0);
    }

    #[Test]
    public function testCountID(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        Assert::same($table->select()->count('id'), 4);
    }

    #[Test]
    public function testCountDistinct(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        Assert::same($table->select()->count('id', true), 4);
        Assert::same($table->select()->count('DISTINCT(id)'), 4);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');

        $schema->save();
    }
}
