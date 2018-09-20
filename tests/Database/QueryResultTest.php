<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests;

use Spiral\Database\Database;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\QueryStatement;
use Spiral\Database\Schema\AbstractTable;
use Spiral\Database\Table;
use Spiral\Pagination\Paginator;

abstract class QueryResultTest extends BaseQueryTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp()
    {
        $this->database = $this->db();

        $schema = $this->database->table('sample_table')->getSchema();
        $schema->primary('id');
        $schema->string('name', 64);
        $schema->integer('value');
        $schema->save();
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    public function fillData(Table $table = null)
    {
        $table = $table ?? $this->database->table('sample_table');

        for ($i = 0; $i < 10; $i++) {
            $table->insertOne([
                'name'  => md5($i),
                'value' => $i * 10
            ]);
        }
    }

    public function tearDown()
    {
        $this->dropDatabase($this->database);
    }

    public function testInstance()
    {
        $table = $this->database->table('sample_table');

        $this->assertInstanceOf(QueryStatement::class, $table->select()->getIterator());
        $this->assertInstanceOf(\PDOStatement::class, $table->select()->getIterator());
    }

    //We are testing only extended functionality, there is no need to test PDOStatement

    public function testCountColumns()
    {
        $table = $this->database->table('sample_table');
        $result = $table->select()->getIterator();

        $this->assertSame(3, $result->countColumns());

        $this->assertInternalType('array', $result->__debugInfo());
    }

    public function testIterateOver()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->getIterator();

        $i = 0;
        foreach ($result as $item) {
            $this->assertEquals(md5($i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSameQuery(
            'SELECT * FROM {sample_table}',
            $result->queryString
        );

        $this->assertSame(10, $i);
    }

    public function testIterateOverLimit()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->limit(5)->getIterator();

        $i = 0;
        foreach ($result as $item) {
            $this->assertEquals(md5($i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSame(5, $i);
    }

    public function testIterateOverOffset()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->offset(5)->getIterator();

        $i = 5;
        foreach ($result as $item) {
            $this->assertEquals(md5($i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSame(10, $i);
    }

    public function testIterateOverOffsetAndLimit()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->offset(5)->limit(2)->getIterator();

        $i = 5;
        foreach ($result as $item) {
            $this->assertEquals(md5($i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSame(7, $i);
    }

    public function testPaginate()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $paginator = new Paginator(2);

        $select = $table->select();

        $select->setPaginator($paginator->withPage(1));

        $i = 0;
        foreach ($select as $item) {
            $this->assertEquals(md5($i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSame(2, $i);

        $select->setPaginator($paginator->withPage(2));

        $i = 2;
        foreach ($select as $item) {
            $this->assertEquals(md5($i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSame(4, $i);

        $select->setPaginator($paginator->withPage(3));

        $i = 4;
        foreach ($select as $item) {
            $this->assertEquals(md5($i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSame(6, $i);

        $paginator = $paginator->withLimit(6);
        $select->setPaginator($paginator->withPage(4)); //Forced last page

        $i = 6;
        foreach ($select as $item) {
            $this->assertEquals(md5($i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSame(10, $i);
    }

    public function testDebugString()
    {
        $table = $this->database->table('sample_table');
        $result = $table->select()->getIterator();

        $this->assertSameQuery(
            'SELECT * FROM {sample_table}',
            $result->queryString
        );
    }

    public function testToArray()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->limit(1)->getIterator();

        $this->assertEquals([
            ['id' => 1, 'name' => md5(0), 'value' => 0]
        ], $result->toArray());
    }

    /**
     * @expectedException \Spiral\Database\Exception\BuilderException
     */
    public function testBadAggregation()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $table->select()->ha();
    }

    /**
     * @expectedException \Spiral\Database\Exception\BuilderException
     */
    public function testBadAggregation2()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $table->select()->avg();
    }

    /**
     * @expectedException \Spiral\Database\Exception\BuilderException
     */
    public function testBadAggregation3()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $table->select()->avg(1, 2);
    }

    public function testClose()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();
        $result = $table->select()->getIterator();

        $this->assertNull($result->close());
    }

    public function testSpanishInquisition()
    {
        $driver = $this->database->getDriver();
        $driver->connect();
        $this->assertTrue($driver->isConnected());

        //And now something different
        $driver->disconnect();
        $this->assertFalse($driver->isConnected());
    }

    public function testChunks()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $select = $table->select();

        $count = 0;
        $select->runChunks(1, function ($result) use (&$count) {
            $this->assertInstanceOf(QueryStatement::class, $result);
            $this->assertEquals($count + 1, $result->fetchColumn());

            $count++;
        });

        $this->assertSame(10, $count);
    }

    public function testChunksExif()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $select = $table->select();

        $count = 0;
        $select->runChunks(1, function ($result) use (&$count) {
            $this->assertInstanceOf(QueryStatement::class, $result);

            $count++;
            if ($count == 5) {
                return false;
            }
        });

        $this->assertSame(5, $count);
    }

    public function testBindByName()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->getIterator();

        $result->bind('name', $name);

        foreach ($result as $item) {
            $this->assertSame($name, $item['name']);
        }
    }

    public function testBindByNumber()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->getIterator();

        //Id is = 0
        $result->bind(1, $name);

        foreach ($result as $item) {
            $this->assertSame($name, $item['name']);
        }
    }

    public function testNativeParameters()
    {
        $this->fillData();

        $row = $this->database->query(
            'SELECT * FROM sample_table WHERE id = ?',
            [6]
        )->fetch();

        $i = 5;
        $this->assertEquals(md5($i), $row['name']);
        $this->assertEquals($i * 10, $row['value']);

        $row = $this->database->query(
            'SELECT * FROM sample_table WHERE id = :id',
            [':id' => 5]
        )->fetch();

        $i = 4;
        $this->assertEquals(md5($i), $row['name']);
        $this->assertEquals($i * 10, $row['value']);
    }

    public function testDatetimeInQuery()
    {
        $this->fillData();

        $this->assertSame(10, $this->database->sample_table->select()
            ->where('name', '!=', new \DateTime('1990-01-01'))
            ->count());
    }

    /**
     * @expectedException \Spiral\Database\Exception\DriverException
     * @expectedExceptionMessage Array parameters can not be named
     */
    public function testNativeParametersError()
    {
        $this->fillData();

        $row = $this->database->query(
            'SELECT * FROM sample_table WHERE id = :id',
            [':id' => [1, 2]]
        )->fetch();

        $i = 4;
        $this->assertEquals(md5($i), $row['name']);
        $this->assertEquals($i * 10, $row['value']);
    }

    public function testUnpackArrayFromParameter()
    {
        $this->fillData();

        $rows = $this->database->query(
            'SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC',
            [new Parameter([1, 2, 3])]
        )->fetchAll();

        $i = 0;
        $this->assertEquals(md5($i), $rows[0]['name']);
        $this->assertEquals($i * 10, $rows[0]['value']);

        $i = 1;
        $this->assertEquals(md5($i), $rows[1]['name']);
        $this->assertEquals($i * 10, $rows[1]['value']);

        $i = 2;
        $this->assertEquals(md5($i), $rows[2]['name']);
        $this->assertEquals($i * 10, $rows[2]['value']);
    }
}