<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Database\Tests;

use Spiral\Database\Entity\Database;
use Spiral\Database\Schema\Prototypes\AbstractTable;

abstract class TransactionsTest extends BaseTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp()
    {
        $this->database = $this->database();

        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    public function tearDown()
    {
        $this->dropAll($this->database());
    }

    public function testCommitTransactionInsert()
    {
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        $this->assertSame(1, $this->database->table->count());

        $this->database->commit();

        $this->assertSame(1, $this->database->table->count());
    }

    public function testCommitTransactionInsertClosure()
    {
        $db = $this->database;

        $this->database->transaction(function () use ($db) {
            $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
            $this->assertSame(1, $this->database->table->count());
        });

        $this->assertSame(1, $this->database->table->count());
    }

    public function testCommitTransactionInsertClosureIsolationLevel()
    {
        $db = $this->database;

        $this->database->transaction(function () use ($db) {
            $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
            $this->assertSame(1, $this->database->table->count());
        }, Database::ISOLATION_READ_COMMITTED);

        $this->assertSame(1, $this->database->table->count());
    }

    public function testCommitTransactionInsertClosureIsolationLevel1()
    {
        $db = $this->database;

        $this->database->transaction(function () use ($db) {
            $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
            $this->assertSame(1, $this->database->table->count());
        }, Database::ISOLATION_READ_UNCOMMITTED);

        $this->assertSame(1, $this->database->table->count());
    }

    public function testCommitTransactionInsertClosureIsolationLevel2()
    {
        $db = $this->database;

        $this->database->transaction(function () use ($db) {
            $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
            $this->assertSame(1, $this->database->table->count());
        }, Database::ISOLATION_REPEATABLE_READ);

        $this->assertSame(1, $this->database->table->count());
    }

    public function testCommitTransactionInsertClosureIsolationLevel3()
    {
        $db = $this->database;

        $this->database->transaction(function () use ($db) {
            $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
            $this->assertSame(1, $this->database->table->count());
        }, Database::ISOLATION_SERIALIZABLE);

        $this->assertSame(1, $this->database->table->count());
    }

    public function testRollbackTransactionInsert()
    {
        $this->database->begin();

        $this->database->table->insertOne(['name' => 'Anton', 'value' => 123]);
        $this->assertSame(1, $this->database->table->count());

        $this->database->rollback();

        $this->assertSame(0, $this->database->table->count());
    }

    public function testRollbackTransactionInsertClosure()
    {
        $db = $this->database;

        try {
            $this->database->transaction(function () use ($db) {
                $db->table->insertOne(['name' => 'Anton', 'value' => 123]);
                $this->assertSame(1, $this->database->table->count());

                throw new \Error('Something happen');
            });
        } catch (\Error $e) {
            $this->assertSame('Something happen', $e->getMessage());
        }

        $this->assertSame(0, $this->database->table->count());
    }

    public function testCommitTransactionNestedInsert()
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

    public function testRollbackTransactionNestedInsert()
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
}