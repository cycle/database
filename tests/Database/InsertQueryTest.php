<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
declare(strict_types=1);

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

    public function setUp(): void
    {
        $this->database = $this->db();
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    public function testQueryInstance(): void
    {
        $this->assertInstanceOf(InsertQuery::class, $this->database->insert());
    }

    public function testQueryInstanceViaTable(): void
    {
        $this->assertInstanceOf(InsertQuery::class, $this->database->table->insert());
    }

    //Generic behaviours

    public function testSimpleInsert(): void
    {
        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton'
        ]);

        $this->assertSameQuery('INSERT INTO {table} ({name}) VALUES (?)', $insert);
    }

    public function testSimpleInsertWithStatesValues(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100);

        $this->assertSameQuery('INSERT INTO {table} ({name}, {balance}) VALUES (?, ?)', $insert);
    }

    public function testSimpleInsertMultipleRows(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->values('John', 200);

        $this->assertSameQuery('INSERT INTO {table} ({name}, {balance}) VALUES (?, ?), (?, ?)', $insert);
    }
}
