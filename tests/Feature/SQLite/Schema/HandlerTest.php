<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLite\Schema;

use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Test;

#[Test]
final class HandlerTest extends DatabaseTestCase
{
    public function tableNamesLeaveOutTheSequenceTable(): void
    {
        $this->database->execute('CREATE TABLE counters (id INTEGER PRIMARY KEY AUTOINCREMENT, value INTEGER)');
        $this->database->table('counters')->insertOne(['value' => 1]);

        $tables = $this->database->getDriver()->getSchemaHandler()->getTableNames();

        Assert::same($tables, ['counters']);
        Assert::same($this->database->query("SELECT name FROM sqlite_master WHERE name = 'sqlite_sequence'")->fetchColumn(), 'sqlite_sequence');
    }

    /**
     * SQLite cannot alter columns or foreign keys in place: these changes go through a table rebuild in
     * `syncTable()`, so the per-element operations do nothing.
     */
    public function inPlaceColumnAndForeignKeyOperationsDoNothing(): void
    {
        $this->makeTable('parents', ['id' => 'primary']);
        $this->makeTable('items', ['id' => 'primary', 'name' => 'string(32)', 'parent_id' => 'integer']);
        $handler = $this->database->getDriver()->getSchemaHandler();
        $schema = $this->schema('items');
        $foreignKey = $schema->foreignKey(['parent_id'])->references('parents', ['id']);

        $handler->createColumn($schema, $schema->string('extra'));
        $handler->alterColumn($schema, $schema->column('name'), $schema->column('name')->string(64));
        $handler->dropColumn($schema, $schema->column('name'));
        $handler->createForeignKey($schema, $foreignKey);
        $handler->alterForeignKey($schema, $foreignKey, $foreignKey);
        $handler->dropForeignKey($schema, $foreignKey);

        $actual = $this->schema('items');
        Assert::same(\array_keys($actual->getColumns()), ['id', 'name', 'parent_id']);
        Assert::same($actual->getForeignKeys(), []);
    }
}
