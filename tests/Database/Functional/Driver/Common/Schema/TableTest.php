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
    public function setUp(): void
    {
        parent::setUp();

        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');

        $schema->save();
    }

    public function testGetSchema(): void
    {
        $this->assertIsArray($this->database->getDriver()->__debugInfo());
        $this->assertInstanceOf(Table::class, $this->database->table('table'));
        $this->assertInstanceOf(AbstractTable::class, $this->database->table('table')->getSchema());
    }

    public function testExistsAndEmpty(): void
    {
        $table = $this->database->table('table');
        $this->assertSame('table', $table->getFullName());

        $this->assertTrue($table->exists());
        $this->assertSame(0, $table->count());

        $this->assertTrue($table->hasColumn('value'));
        $this->assertFalse($table->hasColumn('xx'));
    }

    public function testPrimaryKeys(): void
    {
        $table = $this->database->table('table');

        $this->assertSame(['id'], $table->getPrimaryKeys());
    }

    public function testHasIndex(): void
    {
        $table = $this->database->table('table');

        $this->assertFalse($table->hasIndex(['value']));

        $schema = $table->getSchema();
        $schema->index(['value']);
        $schema->save();

        $this->assertTrue($table->hasIndex(['value']));
    }

    public function testGetIndexes(): void
    {
        $table = $this->database->table('table');

        $this->assertCount(0, $table->getIndexes());

        $schema = $table->getSchema();
        $schema->index(['value']);
        $schema->save();

        $this->assertCount(1, $table->getIndexes());
    }

    public function testHasForeignKey(): void
    {
        $schema = $this->database->table('table2')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();

        $table = $this->database->table('table');

        $this->assertFalse($table->hasForeignKey(['external_id']));

        $schema = $table->getSchema();
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('table2', ['id']);
        $schema->save();

        $this->assertTrue($table->hasForeignKey(['external_id']));
    }

    public function testGetForeignKeys(): void
    {
        $schema = $this->database->table('table2')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();

        $table = $this->database->table('table');

        $this->assertCount(0, $table->getForeignKeys());

        $schema = $table->getSchema();
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('table2', ['id']);
        $schema->save();

        $this->assertCount(1, $table->getForeignKeys());
    }

    public function testDependencies(): void
    {
        $schema = $this->database->table('table2')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();

        $table = $this->database->table('table');

        $this->assertCount(0, $table->getDependencies());

        $schema = $table->getSchema();
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('table2', ['id']);
        $schema->save();

        $this->assertSame(['table2'], $table->getDependencies());
    }

    //see old versions of postgres
    public function testGetColumns(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[$column->getName()] = $column->getAbstractType();
        }

        $this->assertSame(
            [
                'id' => 'primary',
                'name' => 'text',
                'value' => 'integer',
            ],
            $columns
        );
    }

    public function testInsertOneRow(): void
    {
        $table = $this->database->table('table');

        $this->assertSame(0, $table->count());

        $id = $table->insertOne(
            [
                'name' => 'Anton',
                'value' => 10,
            ]
        );

        $this->assertNotNull($id);
        $this->assertSame(1, $id);

        $this->assertSame(1, $table->count());

        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'Anton', 'value' => 10],
            ],
            $table->fetchAll()
        );
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testInsertTypedEnum(): void
    {
        $table = $this->database->table('table');

        $this->assertSame(0, $table->count());

        $id = $table->insertOne(
            [
                'name' => FooBarEnum::FOO,
                'value' => IntegerEnum::HUNDRED,
            ]
        );

        $this->assertNotNull($id);

        $this->assertEquals(
            [
                ['id' => 1, 'name' => FooBarEnum::FOO->value, 'value' => IntegerEnum::HUNDRED->value],
            ],
            $table->fetchAll()
        );
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testInsertTypelessEnum(): void
    {
        $table = $this->database->table('table');

        $this->assertSame(0, $table->count());

        $this->expectException(StatementException::class);

        $table->insertOne(
            [
                'name' => 'Leo',
                'value' => UntypedEnum::FOO,
            ]
        );
    }

    public function testInsertOneRowAfterAnother(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $id = $table->insertOne(
            [
                'name' => 'Anton',
                'value' => 10,
            ]
        );

        $this->assertNotNull($id);
        $this->assertSame(1, $id);

        $id = $table->insertOne(
            [
                'name' => 'John',
                'value' => 20,
            ]
        );

        $this->assertNotNull($id);
        $this->assertSame(2, $id);

        $this->assertSame(2, $table->count());

        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'Anton', 'value' => 10],
                ['id' => 2, 'name' => 'John', 'value' => 20],
            ],
            $table->fetchAll()
        );
    }

    public function testInsertMultiple(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ]
        );

        $this->assertSame(4, $table->count());

        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'Anton', 'value' => 10],
                ['id' => 2, 'name' => 'John', 'value' => 20],
                ['id' => 3, 'name' => 'Bob', 'value' => 30],
                ['id' => 4, 'name' => 'Charlie', 'value' => 40],
            ],
            $table->fetchAll()
        );
    }

    public function testAggregationByPass(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(100, (int) $table->sum('value'));
    }

    public function testAggregationMinByPass(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(10, (int) $table->min('value'));
    }

    public function testAggregationMaxByPass(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(40, (int) $table->max('value'));
    }

    public function testAggregationAvgByPass(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40],
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(25, (int) $table->avg('value'));
    }

    public function testAggregationAvgByPassFloat(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(13.75, (float) $table->avg('value'));
    }

    public function testDeleteWithWhere(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(2, $table->delete(['value' => 10])->run());
        $this->assertSame(0, $table->select()->where(['value' => 10])->count());
    }

    public function testUpdateWithWhere(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(2, $table->update(['value' => 100])->where('value', 10)->run());
        $this->assertSame(2, $table->select()->where(['value' => 100])->count());
    }

    public function testUpdateWithFragment(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(
            2,
            $table->update(['value' => new Expression('value * 2')])->where('value', 10)->run()
        );

        $this->assertSame(3, $table->select()->where(['value' => 20])->count());
    }

    public function testTruncate(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ]
        );

        $this->assertSame(4, $table->count());
        $table->eraseData();
        $this->assertSame(0, $table->count());
    }

    public function testCountID(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ]
        );

        $this->assertSame(4, $table->select()->count('id'));
    }

    public function testCountDistinct(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ]
        );

        $this->assertSame(4, $table->select()->count('DISTINCT(id)'));
    }
}
