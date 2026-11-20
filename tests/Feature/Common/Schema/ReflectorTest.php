<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Schema;

use Cycle\Database\Exception\HandlerException;
use Cycle\Database\Schema\Reflector;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Test;

final class ReflectorTest extends DatabaseTestCase
{
    #[Test]
    public function testCreateNotLinkedTables(): void
    {
        $schemaA = $this->schema('a');
        Assert::false($schemaA->exists());

        $schemaB = $this->schema('b');
        Assert::false($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);

        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);
    }

    #[Test]
    public function testCreateLinkedTablesDirectOrder(): void
    {
        $schemaA = $this->schema('a');
        Assert::false($schemaA->exists());

        $schemaB = $this->schema('b');
        Assert::false($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaB->primary('id');
        $schemaB->string('value');
        $schemaB->integer('a_id');
        $schemaB->foreignKey(['a_id'])->references('a', ['id']);

        $this->saveTables([$schemaA, $schemaB]);

        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);
    }

    #[Test]
    public function testCreateLinkedTablesReversedOrder(): void
    {
        $schemaA = $this->schema('a');
        Assert::false($schemaA->exists());

        $schemaB = $this->schema('b');
        Assert::false($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');
        $schemaB->integer('b_id');
        $schemaB->foreignKey(['b_id'])->references('b', ['id']);

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);

        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);
    }

    #[Test]
    public function testCreateRecursiveLinkedTables(): void
    {
        $schemaA = $this->schema('a');
        Assert::false($schemaA->exists());

        $schemaB = $this->schema('b');
        Assert::false($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreignKey(['b_id'])->references('b', ['id']);

        $schemaB->primary('id');
        $schemaB->string('value');

        $schemaB->integer('a_id');
        $schemaB->foreignKey(['a_id'])->references('a', ['id']);

        $this->saveTables([$schemaA, $schemaB]);

        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);
    }

    #[Test]
    public function testCreateTableAndLinkItAfter(): void
    {
        $schemaA = $this->schema('a');
        Assert::false($schemaA->exists());

        $schemaB = $this->schema('b');
        Assert::false($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);

        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);

        $schemaA->integer('b_id');
        $schemaA->foreignKey(['b_id'])->references('b', ['id']);

        $schemaB->integer('a_id');
        $schemaB->foreignKey(['a_id'])->references('a', ['id']);

        $this->saveTables([$schemaA, $schemaB]);

        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);
    }

    #[Test]
    public function testCreateLinkedAndDropForeign(): void
    {
        $schemaA = $this->schema('a');
        Assert::false($schemaA->exists());

        $schemaB = $this->schema('b');
        Assert::false($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreignKey(['b_id'])->references('b', ['id']);

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);

        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);

        $schemaA->dropForeignKey(['b_id']);

        $this->saveTables([$schemaA, $schemaB]);

        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);
    }

    #[Test]
    public function testCreate3LinkedTables(): void
    {
        $schemaA = $this->schema('a');
        Assert::false($schemaA->exists());

        $schemaB = $this->schema('b');
        Assert::false($schemaB->exists());

        $schemaC = $this->schema('c');
        Assert::false($schemaC->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('c_id');
        $schemaA->foreignKey(['c_id'])->references('c', ['id']);

        $schemaB->primary('id');
        $schemaB->string('value');

        $schemaB->integer('a_id');
        $schemaB->foreignKey(['a_id'])->references('a', ['id']);

        $schemaC->primary('id');
        $schemaC->boolean('value');

        $schemaC->integer('b_id');
        $schemaC->foreignKey(['b_id'])->references('b', ['id']);

        $this->saveTables([$schemaA, $schemaB, $schemaC]);

        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);
        SchemaAssert::sameAsInDb($schemaC);
    }

    #[Test]
    public function testAddColumnsToTables(): void
    {
        $schemaA = $this->schema('a');
        Assert::false($schemaA->exists());

        $schemaB = $this->schema('b');
        Assert::false($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreignKey(['b_id'])->references('b', ['id']);

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);
        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);

        $schemaA->enum('status', ['active', 'disabled'])->defaultValue('active');

        $this->saveTables([$schemaA, $schemaB]);
        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);
    }

    #[Test]
    public function testDropColumnsFromTables(): void
    {
        $schemaA = $this->schema('a');
        Assert::false($schemaA->exists());

        $schemaB = $this->schema('b');
        Assert::false($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreignKey(['b_id'])->references('b', ['id']);

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);
        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);

        $schemaA->dropColumn('value');
        $schemaB->dropColumn('value');

        $this->saveTables([$schemaA, $schemaB]);
        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);
    }

    #[Test]
    public function testRenameColumnsInTables(): void
    {
        $schemaA = $this->schema('a');
        Assert::false($schemaA->exists());

        $schemaB = $this->schema('b');
        Assert::false($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreignKey(['b_id'])->references('b', ['id']);

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);
        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);

        $schemaA->renameColumn('value', 'valueA');
        $schemaB->renameColumn('value', 'valueB');

        $this->saveTables([$schemaA, $schemaB]);
        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);
    }

    #[Test]
    public function testAddIndexesToTables(): void
    {
        $schemaA = $this->schema('a');
        Assert::false($schemaA->exists());

        $schemaB = $this->schema('b');
        Assert::false($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreignKey(['b_id'])->references('b', ['id']);

        $schemaB->primary('id');
        $schemaB->string('value');

        $this->saveTables([$schemaA, $schemaB]);
        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);

        $schemaA->enum('status', ['active', 'disabled'])->defaultValue('active');
        $schemaA->index(['status']);
        $schemaB->index(['value']);

        $this->saveTables([$schemaA, $schemaB]);
        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);
    }

    #[Test]
    public function testDropIndexesFromTables(): void
    {
        $schemaA = $this->schema('a');
        Assert::false($schemaA->exists());

        $schemaB = $this->schema('b');
        Assert::false($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreignKey(['b_id'])->references('b', ['id']);

        $schemaA->enum('status', ['active', 'disabled'])->defaultValue('active');
        $schemaA->index(['status']);

        $schemaB->primary('id');
        $schemaB->string('value');
        $schemaB->index(['value']);

        $this->saveTables([$schemaA, $schemaB]);
        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);

        $schemaA->dropIndex(['status']);
        $schemaB->dropIndex(['value']);

        $this->saveTables([$schemaA, $schemaB]);
        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);
    }

    #[Test]
    public function testDeleteTables(): void
    {
        $schemaA = $this->schema('a');
        Assert::false($schemaA->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $this->saveTables([$schemaA]);

        $this->saveTables([$schemaA]);
        SchemaAssert::sameAsInDb($schemaA);

        $schemaA->declareDropped();
        $this->saveTables([$schemaA]);

        Assert::false($schemaA->exists());
        Assert::false($this->fetchSchema($schemaA)->exists());
    }

    #[Test]
    public function testDeleteTablesWithForeignKeys(): void
    {
        $schemaA = $this->schema('a');
        Assert::false($schemaA->exists());

        $schemaB = $this->schema('b');
        Assert::false($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaA->integer('b_id');
        $schemaA->foreignKey(['b_id'])->references('b', ['id']);

        $schemaA->enum('status', ['active', 'disabled'])->defaultValue('active');
        $schemaA->index(['status']);

        $schemaB->primary('id');
        $schemaB->string('value');
        $schemaB->index(['value']);

        $this->saveTables([$schemaA, $schemaB]);
        SchemaAssert::sameAsInDb($schemaA);
        SchemaAssert::sameAsInDb($schemaB);

        $schemaA->declareDropped();

        $this->saveTables([$schemaA]);
        SchemaAssert::sameAsInDb($schemaB);

        Assert::false($schemaA->exists());
        Assert::false($this->fetchSchema($schemaA)->exists());
    }

    #[Test]
    public function testFailedSaveRollsBackTheTransaction(): void
    {
        $stale = $this->schema('a');
        $stale->primary('id');

        $existing = $this->schema('a');
        $existing->primary('id');
        $existing->save();

        $reflector = new Reflector();
        $reflector->addTable($stale);

        $error = null;
        try {
            $reflector->run();
        } catch (\Throwable $error) {
        }

        Assert::instanceOf($error, HandlerException::class);
        Assert::same($this->database->getDriver()->getTransactionLevel(), 0);
    }

    protected function saveTables(array $tables): void
    {
        $reflector = new Reflector();

        foreach ($tables as $table) {
            $reflector->addTable($table);
        }

        Assert::same($reflector->getTables(), $tables);
        $reflector->run();
    }
}
