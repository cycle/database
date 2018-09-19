<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Database\Tests;

use Spiral\Database\TableSorter;
use Spiral\Database\Schema\AbstractTable;

abstract class SynchronizationPoolTest extends BaseTest
{
    public function tearDown()
    {
        $this->dropDatabase($this->db());
    }

    public function schema(string $table, string $prefix = ''): AbstractTable
    {
        return $this->db('default', $prefix)->table($table)->getSchema();
    }

    public function testCreateNotLinkedTables()
    {
        $schemaA = $this->schema('a');
        $this->assertFalse($schemaA->exists());

        $schemaB = $this->schema('b');
        $this->assertFalse($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);

        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);
    }

    public function testCreateLinkedTablesDirectOrder()
    {
        $schemaA = $this->schema('a');
        $this->assertFalse($schemaA->exists());

        $schemaB = $this->schema('b');
        $this->assertFalse($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaB->primary('id');
        $schemaB->string('value');
        $schemaB->integer('a_id');
        $schemaB->foreign('a_id')->references('a', 'id');

        $this->saveTables([$schemaA, $schemaB]);

        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);
    }

    public function testCreateLinkedTablesReversedOrder()
    {
        $schemaA = $this->schema('a');
        $this->assertFalse($schemaA->exists());

        $schemaB = $this->schema('b');
        $this->assertFalse($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');
        $schemaB->integer('b_id');
        $schemaB->foreign('b_id')->references('b', 'id');

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);

        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);
    }

    public function testCreateRecursiveLinkedTables()
    {
        $schemaA = $this->schema('a');
        $this->assertFalse($schemaA->exists());

        $schemaB = $this->schema('b');
        $this->assertFalse($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreign('b_id')->references('b', 'id');

        $schemaB->primary('id');
        $schemaB->string('value');

        $schemaB->integer('a_id');
        $schemaB->foreign('a_id')->references('a', 'id');

        $this->saveTables([$schemaA, $schemaB]);

        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);
    }

    public function testCreateTableAndLinkItAfter()
    {
        $schemaA = $this->schema('a');
        $this->assertFalse($schemaA->exists());

        $schemaB = $this->schema('b');
        $this->assertFalse($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);

        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);

        $schemaA->integer('b_id');
        $schemaA->foreign('b_id')->references('b', 'id');

        $schemaB->integer('a_id');
        $schemaB->foreign('a_id')->references('a', 'id');

        $this->saveTables([$schemaA, $schemaB]);

        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);
    }

    public function testCreateLinkedAndDropForeign()
    {
        $schemaA = $this->schema('a');
        $this->assertFalse($schemaA->exists());

        $schemaB = $this->schema('b');
        $this->assertFalse($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreign('b_id')->references('b', 'id');

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);

        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);

        $schemaA->dropForeign('b_id');

        $this->saveTables([$schemaA, $schemaB]);

        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);
    }

    public function testCreate3LinkedTables()
    {
        $schemaA = $this->schema('a');
        $this->assertFalse($schemaA->exists());

        $schemaB = $this->schema('b');
        $this->assertFalse($schemaB->exists());

        $schemaC = $this->schema('c');
        $this->assertFalse($schemaC->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('c_id');
        $schemaA->foreign('c_id')->references('c', 'id');

        $schemaB->primary('id');
        $schemaB->string('value');

        $schemaB->integer('a_id');
        $schemaB->foreign('a_id')->references('a', 'id');

        $schemaC->primary('id');
        $schemaC->boolean('value');

        $schemaC->integer('b_id');
        $schemaC->foreign('b_id')->references('b', 'id');

        $this->saveTables([$schemaA, $schemaB, $schemaC]);

        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);
        $this->assertSameAsInDB($schemaC);
    }

    public function testAddColumnsToTables()
    {
        $schemaA = $this->schema('a');
        $this->assertFalse($schemaA->exists());

        $schemaB = $this->schema('b');
        $this->assertFalse($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreign('b_id')->references('b', 'id');

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);
        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);

        $schemaA->enum('status', ['active', 'disabled'])->defaultValue('active');

        $this->saveTables([$schemaA, $schemaB]);
        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);
    }

    public function testDropColumnsFromTables()
    {
        $schemaA = $this->schema('a');
        $this->assertFalse($schemaA->exists());

        $schemaB = $this->schema('b');
        $this->assertFalse($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreign('b_id')->references('b', 'id');

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);
        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);

        $schemaA->dropColumn('value');
        $schemaB->dropColumn('value');

        $this->saveTables([$schemaA, $schemaB]);
        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);
    }

    public function testRenameColumnsInTables()
    {
        $schemaA = $this->schema('a');
        $this->assertFalse($schemaA->exists());

        $schemaB = $this->schema('b');
        $this->assertFalse($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreign('b_id')->references('b', 'id');

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);
        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);

        $schemaA->renameColumn('value', 'valueA');
        $schemaB->renameColumn('value', 'valueB');

        $this->saveTables([$schemaA, $schemaB]);
        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);
    }

    public function testAddIndexesToTables()
    {
        $schemaA = $this->schema('a');
        $this->assertFalse($schemaA->exists());

        $schemaB = $this->schema('b');
        $this->assertFalse($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreign('b_id')->references('b', 'id');

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);
        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);

        $schemaA->enum('status', ['active', 'disabled'])->defaultValue('active');
        $schemaA->index(['status']);
        $schemaB->index(['value']);

        $this->saveTables([$schemaA, $schemaB]);
        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);
    }

    public function testDropIndexesFromTables()
    {
        $schemaA = $this->schema('a');
        $this->assertFalse($schemaA->exists());

        $schemaB = $this->schema('b');
        $this->assertFalse($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreign('b_id')->references('b', 'id');

        $schemaA->enum('status', ['active', 'disabled'])->defaultValue('active');
        $schemaA->index(['status']);

        $schemaB->primary('id');
        $schemaB->string('value');
        $schemaB->index(['value']);

        $this->saveTables([$schemaA, $schemaB]);
        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);

        $schemaA->dropIndex(['status']);
        $schemaB->dropIndex(['value']);

        $this->saveTables([$schemaA, $schemaB]);
        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);
    }


    protected function saveTables(array $tables)
    {
        $pool = new TableSorter($tables);
        $this->assertSame($tables, $pool->getTables());
        $pool->run();
    }
}