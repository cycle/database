<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests\Postgres;

use Spiral\Database\Driver\Postgres\Query\PostgresInsertQuery;

class InsertQueryTest extends \Spiral\Database\Tests\InsertQueryTest
{
    const DRIVER = 'postgres';

    public function setUp()
    {
        parent::setUp();

        //To test PG insert behaviour rendering
        $schema = $this->database->table('target_table')->getSchema();
        $schema->primary('target_id');
        $schema->save();
    }

    public function tearDown()
    {
        $this->dropDatabase($this->database);
    }

    public function testQueryInstance()
    {
        parent::testQueryInstance();
        $this->assertInstanceOf(PostgresInsertQuery::class, $this->database->insert());
    }

    //Generic behaviours

    public function testSimpleInsert()
    {
        $insert = $this->database->insert()->into('target_table')->values([
            'name' => 'Anton'
        ]);

        $this->assertSameQuery(
            "INSERT INTO {target_table} ({name}) VALUES (?) RETURNING {target_id}",
            $insert
        );
    }

    public function testSimpleInsertWithStatesValues()
    {
        $insert = $this->database->insert()->into('target_table')
            ->columns('name', 'balance')
            ->values('Anton', 100);

        $this->assertSameQuery(
            "INSERT INTO {target_table} ({name}, {balance}) VALUES (?, ?) RETURNING {target_id}",
            $insert
        );
    }

    public function testSimpleInsertMultipleRows()
    {
        $insert = $this->database->insert()->into('target_table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->values('John', 200);

        $this->assertSameQuery(
            "INSERT INTO {target_table} ({name}, {balance}) VALUES (?, ?), (?, ?) RETURNING {target_id}",
            $insert
        );
    }
}