<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use Spiral\Database\Database;
use Spiral\Database\Schema\AbstractTable;
use Spiral\Database\StatementInterface;
use Spiral\Database\Table;
use Spiral\Pagination\Paginator;

abstract class StatementTest extends BaseTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp(): void
    {
        $this->database = $this->db();

        $schema = $this->database->table('sample_table')->getSchema();
        $schema->primary('id');
        $schema->string('name', 64);
        $schema->integer('value');
        $schema->save();
    }

    public function tearDown(): void
    {
        $this->dropDatabase($this->database);
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    public function fillData(Table $table = null): void
    {
        $table = $table ?? $this->database->table('sample_table');

        for ($i = 0; $i < 10; $i++) {
            $table->insertOne(
                [
                    'name'  => md5((string)$i),
                    'value' => $i * 10
                ]
            );
        }
    }

    public function testInstance(): void
    {
        $table = $this->database->table('sample_table');

        $this->assertInstanceOf(
            StatementInterface::class,
            $table->select()->getIterator()
        );

        $this->assertInstanceOf(
            \PDOStatement::class,
            $table->select()->run()->getPDOStatement()
        );
    }

    //We are testing only extended functionality, there is no need to test PDOStatement

    public function testCountColumns(): void
    {
        $table = $this->database->table('sample_table');
        $result = $table->select()->getIterator();

        $this->assertSame(3, $result->columnCount());
    }

    public function testIterateOver(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->getIterator();

        $i = 0;
        foreach ($result as $item) {
            $this->assertEquals(md5((string)$i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSameQuery(
            'SELECT * FROM {sample_table}',
            $result->getQueryString()
        );

        $this->assertSame(10, $i);
    }

    public function testIterateOverLimit(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->limit(5)->getIterator();

        $i = 0;
        foreach ($result as $item) {
            $this->assertEquals(md5((string)$i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSame(5, $i);
    }

    public function testIterateOverOffset(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->offset(5)->getIterator();

        $i = 5;
        foreach ($result as $item) {
            $this->assertEquals(md5((string)$i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSame(10, $i);
    }

    public function testIterateOverOffsetAndLimit(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->offset(5)->limit(2)->getIterator();

        $i = 5;
        foreach ($result as $item) {
            $this->assertEquals(md5((string)$i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSame(7, $i);
    }

    public function testPaginate(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $paginator = new Paginator(2);

        $select = $table->select();
        $paginator->withPage(1)->paginate($select);

        $i = 0;
        foreach ($select as $item) {
            $this->assertEquals(md5((string)$i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSame(2, $i);

        $select = $table->select();
        $paginator->withPage(2)->paginate($select);
        $i = 2;
        foreach ($select as $item) {
            $this->assertEquals(md5((string)$i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSame(4, $i);

        $select = $table->select();
        $paginator->withPage(3)->paginate($select);

        $i = 4;
        foreach ($select as $item) {
            $this->assertEquals(md5((string)$i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSame(6, $i);

        $paginator = $paginator->withLimit(6);

        $select = $table->select();
        $paginator->withPage(4)->paginate($select); //Forced last page

        $i = 6;
        foreach ($select as $item) {
            $this->assertEquals(md5((string)$i), $item['name']);
            $this->assertEquals($i * 10, $item['value']);

            $i++;
        }

        $this->assertSame(10, $i);
    }

    public function testDebugString(): void
    {
        $table = $this->database->table('sample_table');
        $result = $table->select()->getIterator();

        $this->assertSameQuery(
            'SELECT * FROM {sample_table}',
            $result->getQueryString()
        );
    }

    public function testToArray(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->limit(1)->getIterator();

        $this->assertEquals(
            [
                ['id' => 1, 'name' => md5('0'), 'value' => 0]
            ],
            $result->fetchAll()
        );
    }

    public function testClose(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();
        $result = $table->select()->getIterator();

        $this->assertNull($result->close());
    }

    public function testSpanishInquisition(): void
    {
        $driver = $this->database->getDriver();
        $driver->connect();
        $this->assertTrue($driver->isConnected());

        //And now something different
        $driver->disconnect();
        $this->assertFalse($driver->isConnected());
    }

    public function testChunks(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $select = $table->select();

        $count = 0;
        $select->runChunks(
            1,
            function ($result) use (&$count): void {
                $this->assertInstanceOf(StatementInterface::class, $result);
                $this->assertEquals($count + 1, $result->fetchColumn());

                $count++;
            }
        );

        $this->assertSame(10, $count);
    }

    public function testChunksExif(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $select = $table->select();

        $count = 0;
        $select->runChunks(
            1,
            function ($result) use (&$count) {
                $this->assertInstanceOf(StatementInterface::class, $result);

                $count++;
                if ($count == 5) {
                    return false;
                }
            }
        );

        $this->assertSame(5, $count);
    }

    public function testNativeParameters(): void
    {
        $this->fillData();

        $row = $this->database->query(
            'SELECT * FROM sample_table WHERE id = ?',
            [6]
        )->fetch();

        $i = 5;
        $this->assertEquals(md5((string)$i), $row['name']);
        $this->assertEquals($i * 10, $row['value']);

        $row = $this->database->query(
            'SELECT * FROM sample_table WHERE id = :id',
            [':id' => 5]
        )->fetch();

        $i = 4;
        $this->assertEquals(md5((string)$i), $row['name']);
        $this->assertEquals($i * 10, $row['value']);
    }

    public function testDatetimeInQuery(): void
    {
        $this->fillData();

        $this->assertSame(
            10,
            $this->database->sample_table->select()
                ->where('name', '!=', new \DateTime('1990-01-01'))
                ->count()
        );
    }

    /**
     * @expectedException \Spiral\Database\Exception\StatementException
     */
    public function testNativeParametersError(): void
    {
        $this->fillData();

        $row = $this->database->query(
            'SELECT * FROM sample_table WHERE id = :id',
            [':id' => [1, 2]]
        )->fetch();

        $i = 4;
        $this->assertEquals(md5((string)$i), $row['name']);
        $this->assertEquals($i * 10, $row['value']);
    }

    public function testUnpackArrayFromParameter(): void
    {
        $this->fillData();

        $rows = $this->database->query(
            'SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC',
            [1, 2, 3]
        )->fetchAll();

        $i = 0;
        $this->assertEquals(md5((string)$i), $rows[0]['name']);
        $this->assertEquals($i * 10, $rows[0]['value']);

        $i = 1;
        $this->assertEquals(md5((string)$i), $rows[1]['name']);
        $this->assertEquals($i * 10, $rows[1]['value']);

        $i = 2;
        $this->assertEquals(md5((string)$i), $rows[2]['name']);
        $this->assertEquals($i * 10, $rows[2]['value']);
    }
}
