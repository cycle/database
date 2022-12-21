<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Query;

use Cycle\Database\Database;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class TransactionsTest extends BaseTest
{
    public function setUp(): void
    {
        parent::setUp();

        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();
    }

    public function testCommitTransactionInsert(): void
    {
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        $this->assertSame(1, $this->database->table->count());

        $this->database->commit();

        $this->assertSame(1, $this->database->table->count());
    }

    public function testCommitTransactionInsertClosure(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                $this->assertSame(1, $this->database->table->count());
            }
        );

        $this->assertSame(1, $this->database->table->count());
    }

    public function testCommitTransactionInsertClosureIsolationLevel(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                $this->assertSame(1, $this->database->table->count());
            },
            Database::ISOLATION_READ_COMMITTED
        );

        $this->assertSame(1, $this->database->table->count());
    }

    public function testCommitTransactionInsertClosureIsolationLevel1(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                $this->assertSame(1, $this->database->table->count());
            },
            Database::ISOLATION_READ_UNCOMMITTED
        );

        $this->assertSame(1, $this->database->table->count());
    }

    public function testCommitTransactionInsertClosureIsolationLevel2(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                $this->assertSame(1, $this->database->table->count());
            },
            Database::ISOLATION_REPEATABLE_READ
        );

        $this->assertSame(1, $this->database->table->count());
    }

    public function testCommitTransactionInsertClosureIsolationLevel3(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                $this->assertSame(1, $this->database->table->count());
            },
            Database::ISOLATION_SERIALIZABLE
        );

        $this->assertSame(1, $this->database->table->count());
    }

    public function testRollbackTransactionInsert(): void
    {
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        $this->assertSame(1, $this->database->table->count());

        $this->database->rollback();

        $this->assertSame(0, $this->database->table->count());
    }

    public function testRollbackTransactionInsertClosure(): void
    {
        $db = $this->database;

        try {
            $this->database->transaction(
                function () use ($db): void {
                    $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                    $this->assertSame(1, $this->database->table->count());

                    throw new \Error('Something happen');
                }
            );
        } catch (\Error $e) {
            $this->assertSame('Something happen', $e->getMessage());
        }

        $this->assertSame(0, $this->database->table->count());
    }

    public function testCommitTransactionNestedInsert(): void
    {
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        $this->assertSame(1, $this->database->table->count());

        //Nested savepoint
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'John', 'value' => 456]);
        $this->assertSame(2, $this->database->table->count());

        $this->database->commit();
        $this->assertSame(2, $this->database->table->count());

        $this->database->commit();
        $this->assertSame(2, $this->database->table->count());
    }

    public function testRollbackTransactionNestedInsert(): void
    {
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        $this->assertSame(1, $this->database->table->count());

        //Nested savepoint
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'John', 'value' => 456]);
        $this->assertSame(2, $this->database->table->count());

        $this->database->rollback();
        $this->assertSame(1, $this->database->table->count());

        $this->database->commit();
        $this->assertSame(1, $this->database->table->count());
    }

    public function testRollbackDDLChanges(): void
    {
        if (static::DRIVER === 'mysql') {
            $this->markTestSkipped('MySQL does not support DDL transactions.');
        }

        // Creating a new table with primary field
        $table = $this->database->table('table');
        $schema = $table->getSchema();
        $schema->primary('id');
        $schema->save();

        $this->database->begin();

        // Add a new field on a first transaction level
        $schema->integer('value');
        $schema->save();

        $this->assertTrue($table->hasColumn('value'));

        // Nested savepoint
        $this->database->begin();

        // Add another one field on a second transaction level
        $schema->integer('new_value');
        $schema->save();

        $this->assertTrue($table->hasColumn('new_value'));

        // Revert changes on the second level
        $this->database->rollback();

        // Commit changes on the first level
        $this->database->commit();

        $this->assertTrue($table->hasColumn('value'));
        $this->assertFalse($table->hasColumn('new_value'));
    }

    public function testSelectForUpdateException(): void
    {
        $id = $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        $this->assertSame(1, $this->database->table->count());

        $this->database->begin();
        $user = $this->database->table
            ->select()
            ->where('id', $id)
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->forUpdate()
            ->run()
            ->fetch();

        $this->database->table->update(['value' => 234], ['id' => $user['id']])->run();
        $this->database->commit();

        $this->assertEquals(234, (int)$this->database->table->select()->run()->fetchColumn(2));
    }
}
