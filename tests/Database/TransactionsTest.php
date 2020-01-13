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

abstract class TransactionsTest extends BaseTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp(): void
    {
        $this->database = $this->db();

        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();
    }

    public function tearDown(): void
    {
        $this->dropDatabase($this->db());
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
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
