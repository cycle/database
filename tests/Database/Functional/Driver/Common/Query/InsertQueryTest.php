<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Query;

use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

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

    public function testCompileQueryDefaults(): void
    {
        $insert = $this->db()->insert('table')->values([]);

        $this->assertSameQuery(
            'INSERT INTO {table} DEFAULT VALUES',
            (string)$insert
        );
    }

    public function testSimpleInsert(): void
    {
        $insert = $this->database->insert()->into('table')->values(['name' => 'Anton']);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}) VALUES (?)',
            $insert
        );
    }

    public function testSimpleInsertEmptyDataset(): void
    {
        $insert = $this->database->insert()->into('table')->values([]);

        $this->assertSameQuery(
            'INSERT INTO {table} DEFAULT VALUES',
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

    public function testInsertMultipleRowsAsArray(): void
    {
        $insert = $this->database->insert()->into('table')->values([
            ['name' => 'Anton', 'balance' => 100],
            ['name' => 'John', 'balance' => 200],
        ]);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?), (?, ?)',
            $insert
        );
    }

    public function testInsertWithExpressions(): void
    {
        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton',
            'updated_at' => new Expression('NOW()'),
            'deleted_at' => new Expression('NOW()'),
        ]);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {updated_at}, {deleted_at}) VALUES (?, NOW(), NOW())',
            $insert
        );
        $this->assertSameParameters(['Anton'], $insert);

        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton',
            'updated_at' => new Expression('NOW()'),
            'deleted_at' => null,
        ]);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {updated_at}, {deleted_at}) VALUES (?, NOW(), ?)',
            $insert,
        );
        $this->assertSameParameters(['Anton', null], $insert);
    }

    public function testInsertWithFragmentsThatHaveDifferentStatements(): void
    {
        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton',
            'updated_at' => new Fragment('NOW()'),
            'deleted_at' => new Fragment('NOW()'),
        ]);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {updated_at}, {deleted_at}) VALUES (?, NOW(), NOW())',
            $insert
        );

        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton',
            'updated_at' => new Fragment('NOW()'),
            'deleted_at' => new Fragment('datetime(\'now\')'),
        ]);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {updated_at}, {deleted_at}) VALUES (?, NOW(), datetime(\'now\'))',
            $insert,
        );
    }
}
