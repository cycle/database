<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Query;

use Cycle\Database\Database;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class TransactionsTest extends BaseTest
{
    #[\Testo\Test]
    public function testCommitTransactionInsert(): void
    {
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        \Testo\Assert::same($this->database->table->count(), 1);

        $this->database->commit();

        \Testo\Assert::same($this->database->table->count(), 1);
    }

    #[\Testo\Test]
    public function testCommitTransactionInsertClosure(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                \Testo\Assert::same($this->database->table->count(), 1);
            },
        );

        \Testo\Assert::same($this->database->table->count(), 1);
    }

    #[\Testo\Test]
    public function testCommitTransactionInsertClosureIsolationLevel(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                \Testo\Assert::same($this->database->table->count(), 1);
            },
            Database::ISOLATION_READ_COMMITTED,
        );

        \Testo\Assert::same($this->database->table->count(), 1);
    }

    #[\Testo\Test]
    public function testCommitTransactionInsertClosureIsolationLevel1(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                \Testo\Assert::same($this->database->table->count(), 1);
            },
            Database::ISOLATION_READ_UNCOMMITTED,
        );

        \Testo\Assert::same($this->database->table->count(), 1);
    }

    #[\Testo\Test]
    public function testCommitTransactionInsertClosureIsolationLevel2(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                \Testo\Assert::same($this->database->table->count(), 1);
            },
            Database::ISOLATION_REPEATABLE_READ,
        );

        \Testo\Assert::same($this->database->table->count(), 1);
    }

    #[\Testo\Test]
    public function testCommitTransactionInsertClosureIsolationLevel3(): void
    {
        $db = $this->database;

        $this->database->transaction(
            function () use ($db): void {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                \Testo\Assert::same($this->database->table->count(), 1);
            },
            Database::ISOLATION_SERIALIZABLE,
        );

        \Testo\Assert::same($this->database->table->count(), 1);
    }

    #[\Testo\Test]
    public function testRollbackTransactionInsert(): void
    {
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        \Testo\Assert::same($this->database->table->count(), 1);

        $this->database->rollback();

        \Testo\Assert::same($this->database->table->count(), 0);
    }

    #[\Testo\Test]
    public function testRollbackTransactionInsertClosure(): void
    {
        $db = $this->database;

        try {
            $this->database->transaction(
                function () use ($db): void {
                    $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                    \Testo\Assert::same($this->database->table->count(), 1);

                    throw new \Error('Something happen');
                },
            );
        } catch (\Error $e) {
            \Testo\Assert::same($e->getMessage(), 'Something happen');
        }

        \Testo\Assert::same($this->database->table->count(), 0);
    }

    #[\Testo\Test]
    public function testCommitTransactionNestedInsert(): void
    {
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        \Testo\Assert::same($this->database->table->count(), 1);

        //Nested savepoint
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'John', 'value' => 456]);
        \Testo\Assert::same($this->database->table->count(), 2);

        $this->database->commit();
        \Testo\Assert::same($this->database->table->count(), 2);

        $this->database->commit();
        \Testo\Assert::same($this->database->table->count(), 2);
    }

    #[\Testo\Test]
    public function testRollbackTransactionNestedInsert(): void
    {
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        \Testo\Assert::same($this->database->table->count(), 1);

        //Nested savepoint
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'John', 'value' => 456]);
        \Testo\Assert::same($this->database->table->count(), 2);

        $this->database->rollback();
        \Testo\Assert::same($this->database->table->count(), 1);

        $this->database->commit();
        \Testo\Assert::same($this->database->table->count(), 1);
    }

    #[\Testo\Test]
    public function testRollbackDDLChanges(): void
    {
        if (static::DRIVER === 'mysql') {
            throw new \Testo\Core\Exception\SkipTest('MySQL does not support DDL transactions.');
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

        \Testo\Assert::true($table->hasColumn('value'));

        // Nested savepoint
        $this->database->begin();

        // Add another one field on a second transaction level
        $schema->integer('new_value');
        $schema->save();

        \Testo\Assert::true($table->hasColumn('new_value'));

        // Revert changes on the second level
        $this->database->rollback();

        // Commit changes on the first level
        $this->database->commit();

        \Testo\Assert::true($table->hasColumn('value'));
        \Testo\Assert::false($table->hasColumn('new_value'));
    }

    #[\Testo\Test]
    public function testSelectForUpdateException(): void
    {
        $id = $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        \Testo\Assert::same($this->database->table->count(), 1);

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

        \Testo\Assert::equals((int) $this->database->table->select()->run()->fetchColumn(2), 234);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();
    }
}
