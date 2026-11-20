<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Query;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\InsertQuery;
use Cycle\Testing\Database\Assert\SqlAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Test;

abstract class InsertQueryTest extends DatabaseTestCase
{
    #[Test]
    public function testQueryInstance(): void
    {
        Assert::instanceOf($this->database->insert(), InsertQuery::class);

        Assert::instanceOf($this->database->table->insert(), InsertQuery::class);
    }

    #[Test]
    public function testCompileQuery(): void
    {
        $insert = $this->db()->insert('table')->values(['name' => 'Antony']);

        SqlAssert::same((string) $insert, "INSERT INTO {table} ({name}) VALUES ('Antony')");
    }

    #[Test]
    public function testCompileQueryDefaults(): void
    {
        $insert = $this->db()->insert('table')->values([]);

        SqlAssert::same((string) $insert, 'INSERT INTO {table} DEFAULT VALUES');
    }

    #[Test]
    public function testSimpleInsert(): void
    {
        $insert = $this->database->insert()->into('table')->values(['name' => 'Anton']);

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}) VALUES (?)');
    }

    #[Test]
    public function testSimpleInsertEmptyDataset(): void
    {
        $insert = $this->database->insert()->into('table')->values([]);

        SqlAssert::same($insert, 'INSERT INTO {table} DEFAULT VALUES');
    }

    #[Test]
    public function testSimpleInsertWithStatesValues(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100);

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?)');
    }

    #[Test]
    public function testSimpleInsertMultipleRows(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->values('John', 200);

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?), (?, ?)');
    }

    #[Test]
    public function testInsertMultipleRowsAsArray(): void
    {
        $insert = $this->database->insert()->into('table')->values([
            ['name' => 'Anton', 'balance' => 100],
            ['name' => 'John', 'balance' => 200],
        ]);

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?), (?, ?)');
    }

    #[Test]
    public function testInsertWithExpressions(): void
    {
        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton',
            'updated_at' => new Expression('NOW()'),
            'deleted_at' => new Expression('NOW()'),
        ]);

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {updated_at}, {deleted_at}) VALUES (?, NOW(), NOW())');
        SqlAssert::sameParameters($insert, ['Anton']);

        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton',
            'updated_at' => new Expression('NOW()'),
            'deleted_at' => null,
        ]);

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {updated_at}, {deleted_at}) VALUES (?, NOW(), ?)');
        SqlAssert::sameParameters($insert, ['Anton', null]);
    }

    #[Test]
    public function testInsertWithFragmentsThatHaveDifferentStatements(): void
    {
        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton',
            'updated_at' => new Fragment('NOW()'),
            'deleted_at' => new Fragment('NOW()'),
        ]);

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {updated_at}, {deleted_at}) VALUES (?, NOW(), NOW())');

        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton',
            'updated_at' => new Fragment('NOW()'),
            'deleted_at' => new Fragment('datetime(\'now\')'),
        ]);

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {updated_at}, {deleted_at}) VALUES (?, NOW(), datetime(\'now\'))');
    }

    #[Test]
    public function testInsertWithCustomFragment(): void
    {
        $fragment = \Mockery::mock(FragmentInterface::class)->shouldIgnoreMissing();
        $fragment->shouldReceive('getType')->andReturn(CompilerInterface::FRAGMENT);
        $fragment->shouldReceive('getTokens')->andReturn([
            'fragment' => 'NOW()',
            'parameters' => [],
        ]);

        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton',
            'updated_at' => $fragment,
        ]);

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {updated_at}) VALUES (?, NOW())');
        SqlAssert::sameParameters($insert, ['Anton']);

        // cached query
        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton',
            'updated_at' => $fragment,
        ]);

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {updated_at}) VALUES (?, NOW())');
        SqlAssert::sameParameters($insert, ['Anton']);
    }
}
