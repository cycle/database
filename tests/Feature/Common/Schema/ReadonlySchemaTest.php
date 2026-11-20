<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Schema;

use Cycle\Database\Database;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\ReadonlyHandler;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

/**
 * A driver configured with `readonlySchema` introspects and erases data through its real handler and
 * silently ignores every DDL operation.
 */
#[Test]
final class ReadonlySchemaTest extends DatabaseTestCase
{
    private Database $readonly;

    #[BeforeTest]
    public function createTableBehindReadonlySchema(): void
    {
        $this->readonly = $this->db(driverConfig: ['readonlySchema' => true]);

        // Raw SQL: the readonly handler would ignore `save()`. On SQLite the variant is a separate in-memory
        // database that outlives the test, so the table may be left over from the previous one.
        $this->readonly->execute('DROP TABLE IF EXISTS ro_items');
        $this->readonly->execute('CREATE TABLE ro_items (id INTEGER NOT NULL PRIMARY KEY, value VARCHAR(32))');
        $this->readonly->table('ro_items')->insertOne(['id' => 1, 'value' => 'first']);
    }

    public function introspectionReachesTheDatabase(): void
    {
        $handler = $this->readonly->getDriver()->getSchemaHandler();

        Assert::instanceOf($handler, ReadonlyHandler::class);
        // Postgres lists tables qualified with their schema.
        $tables = \array_map(static fn(string $name): string => \substr(\strrchr('.' . $name, '.'), 1), $handler->getTableNames());
        Assert::contains($tables, 'ro_items');
        Assert::true($handler->hasTable('ro_items'));
        Assert::false($handler->hasTable('ro_missing'));
        Assert::array(\array_keys($handler->getSchema('ro_items')->getColumns()))->sameElementsAs(['id', 'value']);
    }

    public function savingAlteredSchemaKeepsTheTable(): void
    {
        $schema = $this->readonly->table('ro_items')->getSchema();
        $schema->string('extra', 16)->nullable();
        $schema->dropColumn('value');
        $schema->index(['id']);

        $schema->save();

        Assert::array(\array_keys($this->columnsInDb()))->sameElementsAs(['id', 'value']);
        Assert::same($this->readonly->table('ro_items')->count(), 1);
    }

    public function savingNewTableCreatesNothing(): void
    {
        $schema = $this->readonly->table('ro_created')->getSchema();
        $schema->primary('id');
        $schema->string('name');

        $schema->save();

        Assert::false($this->readonly->hasTable('ro_created'));
    }

    public function savingDroppedTableKeepsIt(): void
    {
        $schema = $this->readonly->table('ro_items')->getSchema();
        $schema->declareDropped();

        $schema->save();

        Assert::true($this->readonly->hasTable('ro_items'));
        Assert::same($this->readonly->table('ro_items')->count(), 1);
    }

    public function directDdlCallsAreIgnored(): void
    {
        $handler = $this->readonly->getDriver()->getSchemaHandler();
        $initial = $handler->getSchema('ro_items');
        $schema = $handler->getSchema('ro_items');
        $column = $schema->string('extra', 16);
        $index = $schema->index(['value']);
        $foreignKey = $schema->foreignKey(['value'])->references('ro_items', ['id']);

        $handler->createColumn($schema, $column);
        $handler->alterColumn($schema, $initial->column('value'), $schema->column('value')->string(64));
        $handler->dropColumn($schema, $initial->column('value'));
        $handler->createIndex($schema, $index);
        $handler->alterIndex($schema, $index, $index);
        $handler->dropIndex($schema, $index);
        $handler->createForeignKey($schema, $foreignKey);
        $handler->alterForeignKey($schema, $foreignKey, $foreignKey);
        $handler->dropForeignKey($schema, $foreignKey);
        $handler->dropConstrain($schema, 'ro_items_pk');
        $handler->renameTable('ro_items', 'ro_renamed');

        Assert::array(\array_keys($this->columnsInDb()))->sameElementsAs(['id', 'value']);
        Assert::same($handler->getSchema('ro_items')->getIndexes(), []);
        Assert::same($handler->getSchema('ro_items')->getForeignKeys(), []);
        Assert::false($handler->hasTable('ro_renamed'));
    }

    public function eraseDataDeletesTheRows(): void
    {
        $this->readonly->table('ro_items')->eraseData();

        Assert::same($this->readonly->table('ro_items')->count(), 0);
        Assert::true($this->readonly->hasTable('ro_items'));
    }

    public function clonedDriverKeepsSchemaReadonly(): void
    {
        $driver = $this->readonly->getDriver();
        Assert::instanceOf($driver, Driver::class);

        $handler = $driver->withoutCache()->getSchemaHandler();
        $schema = $handler->getSchema('ro_cloned');
        $schema->primary('id');
        $handler->createTable($schema);

        Assert::instanceOf($handler, ReadonlyHandler::class);
        Assert::true($handler->hasTable('ro_items'));
        Assert::false($handler->hasTable('ro_cloned'));
    }

    /**
     * @return array<non-empty-string, \Cycle\Database\Schema\AbstractColumn>
     */
    private function columnsInDb(): array
    {
        return $this->readonly->getDriver()->getSchemaHandler()->getSchema('ro_items')->getColumns();
    }
}
