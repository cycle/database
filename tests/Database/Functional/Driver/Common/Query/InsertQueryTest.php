<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Query;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class InsertQueryTest extends BaseTest
{
    #[\Testo\Test]
    public function testQueryInstance(): void
    {
        \Testo\Assert::instanceOf($this->database->insert(), InsertQuery::class);

        \Testo\Assert::instanceOf($this->database->table->insert(), InsertQuery::class);
    }

    #[\Testo\Test]
    public function testCompileQuery(): void
    {
        $insert = $this->db()->insert('table')->values(['name' => 'Antony']);

        $this->assertSameQuery(
            "INSERT INTO {table} ({name}) VALUES ('Antony')",
            (string) $insert,
        );
    }

    #[\Testo\Test]
    public function testCompileQueryDefaults(): void
    {
        $insert = $this->db()->insert('table')->values([]);

        $this->assertSameQuery(
            'INSERT INTO {table} DEFAULT VALUES',
            (string) $insert,
        );
    }

    #[\Testo\Test]
    public function testSimpleInsert(): void
    {
        $insert = $this->database->insert()->into('table')->values(['name' => 'Anton']);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}) VALUES (?)',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testSimpleInsertEmptyDataset(): void
    {
        $insert = $this->database->insert()->into('table')->values([]);

        $this->assertSameQuery(
            'INSERT INTO {table} DEFAULT VALUES',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testSimpleInsertWithStatesValues(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?)',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testSimpleInsertMultipleRows(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->values('John', 200);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?), (?, ?)',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testInsertMultipleRowsAsArray(): void
    {
        $insert = $this->database->insert()->into('table')->values([
            ['name' => 'Anton', 'balance' => 100],
            ['name' => 'John', 'balance' => 200],
        ]);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?), (?, ?)',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testInsertWithExpressions(): void
    {
        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton',
            'updated_at' => new Expression('NOW()'),
            'deleted_at' => new Expression('NOW()'),
        ]);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {updated_at}, {deleted_at}) VALUES (?, NOW(), NOW())',
            $insert,
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

    #[\Testo\Test]
    public function testInsertWithFragmentsThatHaveDifferentStatements(): void
    {
        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton',
            'updated_at' => new Fragment('NOW()'),
            'deleted_at' => new Fragment('NOW()'),
        ]);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {updated_at}, {deleted_at}) VALUES (?, NOW(), NOW())',
            $insert,
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

    #[\Testo\Test]
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

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {updated_at}) VALUES (?, NOW())',
            $insert,
        );
        $this->assertSameParameters(['Anton'], $insert);

        // cached query
        $insert = $this->database->insert()->into('table')->values([
            'name' => 'Anton',
            'updated_at' => $fragment,
        ]);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {updated_at}) VALUES (?, NOW())',
            $insert,
        );
        $this->assertSameParameters(['Anton'], $insert);
    }
}
