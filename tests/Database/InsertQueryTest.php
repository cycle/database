<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Database\Tests;

use Spiral\Database\Query\InsertQuery;
use Spiral\Database\Database;
use Spiral\Database\Schema\AbstractTable;

abstract class InsertQueryTest extends BaseQueryTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp()
    {
        $this->database = $this->db();
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    public function testQueryInstance()
    {
        $this->assertInstanceOf(InsertQuery::class, $this->database->insert());
    }

    public function testQueryInstanceViaTable()
    {
        $this->assertInstanceOf(InsertQuery::class, $this->database->table->insert());
    }

    //Generic behaviours

    public function testSimpleInsert()
    {
        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton'
        ]);

        $this->assertSameQuery("INSERT INTO {table} ({name}) VALUES (?)", $insert);
    }

    public function testSimpleInsertWithStatesValues()
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100);

        $this->assertSameQuery("INSERT INTO {table} ({name}, {balance}) VALUES (?, ?)", $insert);
    }

    public function testSimpleInsertMultipleRows()
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->values('John', 200);

        $this->assertSameQuery("INSERT INTO {table} ({name}, {balance}) VALUES (?, ?), (?, ?)", $insert);
    }
}