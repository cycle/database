<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Database\Tests;

use Spiral\Database\Database;
use Spiral\Database\Table;
use Spiral\Database\Injection\Expression;
use Spiral\Database\Schema\AbstractTable;

abstract class TableTest extends BaseTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp()
    {
        $this->database = $this->db();

        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    public function tearDown()
    {
        $this->dropDatabase($this->db());
    }

    public function testGetSchema()
    {
        $this->assertInternalType('array', $this->database->getDriver()->__debugInfo());
        $this->assertInstanceOf(Table::class, $this->database->table('table'));
        $this->assertInstanceOf(AbstractTable::class, $this->database->table('table')->getSchema());
    }

    public function testExistsAndEmpty()
    {
        $table = $this->database->table('table');

        $this->assertTrue($table->getSchema()->exists());
        $this->assertSame(0, $table->count());
    }

    //see old versions of postgres
    public function testGetColumns()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $this->assertSame([
            'id'    => 'primary',
            'name'  => 'text',
            'value' => 'integer'
        ], $table->getColumns());
    }

    public function testInsertOneRow()
    {
        $table = $this->database->table('table');

        $this->assertSame(0, $table->count());

        $id = $table->insertOne([
            'name'  => 'Anton',
            'value' => 10
        ]);

        $this->assertNotNull($id);
        $this->assertSame(1, $id);

        $this->assertSame(1, $table->count());

        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'Anton', 'value' => 10]
            ],
            $table->fetchAll()
        );
    }

    public function testInsertOneRowAfterAnother()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $id = $table->insertOne([
            'name'  => 'Anton',
            'value' => 10
        ]);

        $this->assertNotNull($id);
        $this->assertSame(1, $id);

        $id = $table->insertOne([
            'name'  => 'John',
            'value' => 20
        ]);

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

    public function testInsertMultiple()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40]
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

    public function testAggregationByPass()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40]
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(100, $table->sum('value'));
    }

    public function testAggregationMinByPass()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40]
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(10, $table->min('value'));
    }

    public function testAggregationMaxByPass()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40]
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(40, $table->max('value'));
    }

    public function testAggregationAvgByPass()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 30],
                ['Charlie', 40]
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(25, $table->avg('value'));
    }

    public function testAggregationAvgByPassFloat()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10]
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(13.75, $table->avg('value'));
    }

    public function testDeleteWithWhere()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10]
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(2, $table->delete(['value' => 10])->run());
        $this->assertSame(0, $table->select()->where(['value' => 10])->count());
    }

    public function testUpdateWithWhere()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10]
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(2, $table->update(['value' => 100])->where('value', 10)->run());
        $this->assertSame(2, $table->select()->where(['value' => 100])->count());
    }

    public function testUpdateWithFragment()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10]
            ]
        );

        $this->assertSame(4, $table->count());
        $this->assertSame(
            2,
            $table->update(['value' => new Expression('value * 2')])->where('value', 10)->run()
        );

        $this->assertSame(3, $table->select()->where(['value' => 20])->count());
    }

    public function testTruncate()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10]
            ]
        );

        $this->assertSame(4, $table->count());
        $table->truncateData();
        $this->assertSame(0, $table->count());
    }

    public function testJsonSerialize()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10]
            ]
        );

        $this->assertSame(4, $table->count());

        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'Anton', 'value' => 10],
                ['id' => 2, 'name' => 'John', 'value' => 20],
                ['id' => 3, 'name' => 'Bob', 'value' => 15],
                ['id' => 4, 'name' => 'Charlie', 'value' => 10],
            ],
            $table->jsonSerialize()
        );
    }

    public function testCountID()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10]
            ]
        );

        $this->assertSame(4, $table->select()->count('id'));
    }

    public function testCountDistinct()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10]
            ]
        );

        $this->assertSame(4, $table->select()->count('DISTINCT(id)'));
    }
}