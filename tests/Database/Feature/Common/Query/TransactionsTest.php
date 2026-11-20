<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Query;

use Cycle\Database\Database;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class TransactionsTest extends DatabaseTestCase
{
    #[Test]
    public function testCommitTransactionInsert(): void
    {
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        Assert::same($this->database->table->count(), 1);

        $this->database->commit();

        Assert::same($this->database->table->count(), 1);
    }

    #[Test]
    public function testCommitTransactionInsertClosure(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                Assert::same($this->database->table->count(), 1);
            },
        );

        Assert::same($this->database->table->count(), 1);
    }

    #[Test]
    public function testCommitTransactionInsertClosureIsolationLevel(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                Assert::same($this->database->table->count(), 1);
            },
            Database::ISOLATION_READ_COMMITTED,
        );

        Assert::same($this->database->table->count(), 1);
    }

    #[Test]
    public function testCommitTransactionInsertClosureIsolationLevel1(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                Assert::same($this->database->table->count(), 1);
            },
            Database::ISOLATION_READ_UNCOMMITTED,
        );

        Assert::same($this->database->table->count(), 1);
    }

    #[Test]
    public function testCommitTransactionInsertClosureIsolationLevel2(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                Assert::same($this->database->table->count(), 1);
            },
            Database::ISOLATION_REPEATABLE_READ,
        );

        Assert::same($this->database->table->count(), 1);
    }

    #[Test]
    public function testCommitTransactionInsertClosureIsolationLevel3(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                Assert::same($this->database->table->count(), 1);
            },
            Database::ISOLATION_SERIALIZABLE,
        );

        Assert::same($this->database->table->count(), 1);
    }

    #[Test]
    public function testRollbackTransactionInsert(): void
    {
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        Assert::same($this->database->table->count(), 1);

        $this->database->rollback();

        Assert::same($this->database->table->count(), 0);
    }

    #[Test]
    public function testRollbackTransactionInsertClosure(): void
    {
        $db = $this->database;

        try {
            $this->database->transaction(
                function () use ($db): void {
                    $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                    Assert::same($this->database->table->count(), 1);

                    throw new \Error('Something happen');
                },
            );
        } catch (\Error $e) {
            Assert::same($e->getMessage(), 'Something happen');
        }

        Assert::same($this->database->table->count(), 0);
    }

    #[Test]
    public function testCommitTransactionNestedInsert(): void
    {
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        Assert::same($this->database->table->count(), 1);

        //Nested savepoint
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'John', 'value' => 456]);
        Assert::same($this->database->table->count(), 2);

        $this->database->commit();
        Assert::same($this->database->table->count(), 2);

        $this->database->commit();
        Assert::same($this->database->table->count(), 2);
    }

    #[Test]
    public function testRollbackTransactionNestedInsert(): void
    {
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        Assert::same($this->database->table->count(), 1);

        //Nested savepoint
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'John', 'value' => 456]);
        Assert::same($this->database->table->count(), 2);

        $this->database->rollback();
        Assert::same($this->database->table->count(), 1);

        $this->database->commit();
        Assert::same($this->database->table->count(), 1);
    }

    #[Test]
    #[ExceptDriver(Driver::MySQL)]
    public function testRollbackDDLChanges(): void
    {
        // Creating a new table with primary field
        $table = $this->database->table('table');
        $schema = $table->getSchema();
        $schema->primary('id');
        $schema->save();

        $this->database->begin();

        // Add a new field on a first transaction level
        $schema->integer('value');
        $schema->save();

        Assert::true($table->hasColumn('value'));

        // Nested savepoint
        $this->database->begin();

        // Add another one field on a second transaction level
        $schema->integer('new_value');
        $schema->save();

        Assert::true($table->hasColumn('new_value'));

        // Revert changes on the second level
        $this->database->rollback();

        // Commit changes on the first level
        $this->database->commit();

        Assert::true($table->hasColumn('value'));
        Assert::false($table->hasColumn('new_value'));
    }

    #[Test]
    public function testSelectForUpdateException(): void
    {
        $id = $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        Assert::same($this->database->table->count(), 1);

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

        Assert::equals((int) $this->database->table->select()->run()->fetchColumn(2), 234);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();
    }
}
