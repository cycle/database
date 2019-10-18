<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\Postgres;

use Mockery as m;
use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Driver\MySQL\MySQLCompiler;
use Spiral\Database\Driver\Postgres\Query\PostgresInsertQuery;
use Spiral\Database\Driver\QueryBindings;
use Spiral\Database\Driver\Quoter;

class InsertQueryTest extends \Spiral\Database\Tests\InsertQueryTest
{
    public const DRIVER = 'postgres';

    public function setUp(): void
    {
        parent::setUp();

        //To test PG insert behaviour rendering
        $schema = $this->database->table('target_table')->getSchema();
        $schema->primary('target_id');
        $schema->save();
    }

    public function tearDown(): void
    {
        $this->dropDatabase($this->database);
    }

    public function testQueryInstance(): void
    {
        parent::testQueryInstance();
        $this->assertInstanceOf(PostgresInsertQuery::class, $this->database->insert());
    }

    //Generic behaviours

    public function testSimpleInsert(): void
    {
        $insert = $this->database->insert()->into('target_table')->values([
            'name' => 'Anton'
        ]);

        $this->assertSameQuery(
            'INSERT INTO {target_table} ({name}) VALUES (?) RETURNING {target_id}',
            $insert
        );
    }

    public function testSimpleInsertWithStatesValues(): void
    {
        $insert = $this->database->insert()->into('target_table')
            ->columns('name', 'balance')
            ->values('Anton', 100);

        $this->assertSameQuery(
            'INSERT INTO {target_table} ({name}, {balance}) VALUES (?, ?) RETURNING {target_id}',
            $insert
        );
    }

    public function testSimpleInsertMultipleRows(): void
    {
        $insert = $this->database->insert()->into('target_table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->values('John', 200);

        $this->assertSameQuery(
            'INSERT INTO {target_table} ({name}, {balance}) VALUES (?, ?), (?, ?) RETURNING {target_id}',
            $insert
        );
    }

    /**
     * @expectedException \Spiral\Database\Exception\BuilderException
     */
    public function testInvalidCompiler(): void
    {
        $insert = $this->database->insert()->compile(
            new QueryBindings(),
            new MySQLCompiler(
                new Quoter(m::mock(DriverInterface::class), '')
            )
        );
    }
}
