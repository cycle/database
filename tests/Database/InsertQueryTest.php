<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use Spiral\Database\Query\InsertQuery;

abstract class InsertQueryTest extends BaseTest
{
    public function testQueryInstance(): void
    {
        $this->assertInstanceOf(
            InsertQuery::class,
            $this->database->insert()
        );

        $this->assertInstanceOf(
            InsertQuery::class,
            $this->database->table->insert()
        );
    }

    public function testCompileQuery(): void
    {
        $insert = $this->db()->insert('table')->values(['name' => 'Antony']);

        $this->assertSameQuery(
            "INSERT INTO {table} ({name}) VALUES ('Antony')",
            (string)$insert
        );
    }

    public function testSimpleInsert(): void
    {
        $insert = $this->database->insert()->into('table')->values(
            [
                'name' => 'Anton'
            ]
        );

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}) VALUES (?)',
            $insert
        );
    }

    public function testSimpleInsertWithStatesValues(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?)',
            $insert
        );
    }

    public function testSimpleInsertMultipleRows(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->values('John', 200);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?), (?, ?)',
            $insert
        );
    }
}
