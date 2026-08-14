<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Driver\BulkSchemaProviderInterface;
use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\ReadonlyHandler;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;
use Cycle\Database\Tests\Utils\QueryCounter;

/**
 * Bulk introspection ({@see BulkSchemaProviderInterface::getSchemas()}) must return, for every
 * table, a schema identical to the per-table {@see \Cycle\Database\Driver\HandlerInterface::getSchema()},
 * and must report non-existent tables as empty schemas instead of failing or skipping them.
 */
abstract class BulkIntrospectionTest extends BaseTest
{
    public function testEmptyListReturnsEmptyResult(): void
    {
        $this->assertSame([], $this->bulkProvider()->getSchemas([]));
    }

    public function testResultIsKeyedByInputNameInOrder(): void
    {
        $this->makeSampleSchema();

        $names = ['tags', 'authors', 'books'];
        $schemas = $this->bulkProvider()->getSchemas($names);

        $this->assertSame($names, \array_keys($schemas));
    }

    public function testBulkSchemaMatchesPerTableSchema(): void
    {
        $this->makeSampleSchema();

        $handler = $this->bulkProvider();
        $names = ['authors', 'books', 'tags'];
        $bulk = $handler->getSchemas($names);

        foreach ($names as $name) {
            $this->assertTrue($bulk[$name]->exists(), "Table {$name} must be reported as existing");
            $this->assertSameAsInDB($handler->getSchema($name), $bulk[$name]);
        }
    }

    public function testNonExistentTableIsReturnedAsNewSchema(): void
    {
        $schemas = $this->bulkProvider()->getSchemas(['this_table_does_not_exist']);

        $this->assertArrayHasKey('this_table_does_not_exist', $schemas);
        $this->assertFalse($schemas['this_table_does_not_exist']->exists());
        $this->assertSame(AbstractTable::STATUS_NEW, $schemas['this_table_does_not_exist']->getStatus());
    }

    public function testMixOfExistingAndMissingTables(): void
    {
        $this->makeSampleSchema();

        $schemas = $this->bulkProvider()->getSchemas(['authors', 'missing', 'books']);

        $this->assertTrue($schemas['authors']->exists());
        $this->assertFalse($schemas['missing']->exists());
        $this->assertTrue($schemas['books']->exists());
    }

    public function testPrefixIsHonored(): void
    {
        $db = $this->db('default', 'pre_');
        $schema = $db->table('widgets')->getSchema();
        $schema->primary('id');
        $schema->string('label', 32)->defaultValue('x');
        $schema->save(Handler::DO_ALL);

        $handler = $this->bulkProvider();
        $bulk = $handler->getSchemas(['widgets'], 'pre_');

        $this->assertTrue($bulk['widgets']->exists());
        $this->assertSameAsInDB($handler->getSchema('widgets', 'pre_'), $bulk['widgets']);
    }

    public function testBulkIntrospectionQueryCountDoesNotGrowWithTableCount(): void
    {
        if (!$this->isBatchedProvider()) {
            $this->markTestSkipped('Driver introspects tables one by one.');
        }

        $this->makeSampleSchema();
        for ($i = 0; $i < 6; $i++) {
            $schema = $this->schema("extra_{$i}");
            $schema->primary('id');
            $schema->string("value", 32)->defaultValue('x');
            $schema->save(Handler::DO_ALL);
        }

        $few = $this->countBulkQueries(['authors', 'books']);
        $many = $this->countBulkQueries(
            ['authors', 'books', 'tags', 'extra_0', 'extra_1', 'extra_2', 'extra_3', 'extra_4', 'extra_5'],
        );

        $this->assertSame(
            $few[0],
            $many[0],
            \sprintf(
                "Bulk introspection of 9 tables took %d queries instead of %d.\n\nFew:\n%s\n\nMany:\n%s",
                $many[0],
                $few[0],
                $few[1],
                $many[1],
            ),
        );
    }

    /**
     * Composite primary keys, foreign keys and indexes are the case where the per-table and the
     * batched query could disagree on column order — the batched queries rely on ORDER BY to
     * reproduce it. The identity assertion compares those orders exactly.
     */
    public function testCompositeKeysMatchPerTable(): void
    {
        $parent = $this->schema('composite_parent');
        $parent->integer('part_a')->nullable(false);
        $parent->integer('part_b')->nullable(false);
        $parent->setPrimaryKeys(['part_a', 'part_b']);
        $parent->save(Handler::DO_ALL);

        $child = $this->schema('composite_child');
        $child->primary('id');
        $child->integer('ref_a')->nullable(true);
        $child->integer('ref_b')->nullable(true);
        $child->integer('c1')->defaultValue(0);
        $child->integer('c2')->defaultValue(0);
        $child->index(['c1', 'c2']);
        $child->foreignKey(['ref_a', 'ref_b'])->references('composite_parent', ['part_a', 'part_b']);
        $child->save(Handler::DO_ALL);

        $handler = $this->bulkProvider();
        $bulk = $handler->getSchemas(['composite_parent', 'composite_child']);

        $this->assertSame(['part_a', 'part_b'], $bulk['composite_parent']->getPrimaryKeys());
        $this->assertSameAsInDB($handler->getSchema('composite_parent'), $bulk['composite_parent']);
        $this->assertSameAsInDB($handler->getSchema('composite_child'), $bulk['composite_child']);
    }

    /**
     * Column ORDER is not covered by {@see \Cycle\Database\Tests\Traits\TableAssertions::assertSameAsInDB()}
     * (it matches columns by name), while the batched catalog queries return rows in
     * planner-dependent order unless they ORDER BY the ordinal position. The reorder shows up only
     * when the result set is large enough for the planner to prefer a hash join, hence the dozens
     * of tables with interleaved column types.
     */
    public function testColumnOrderMatchesPerTable(): void
    {
        $names = [];
        for ($i = 0; $i < 30; $i++) {
            $name = "col_order_{$i}";
            $schema = $this->schema($name);
            $schema->primary('id');
            $schema->string('str_0', 64);
            $schema->integer('int_0');
            $schema->string('str_1', 64);
            $schema->integer('int_1');
            $schema->string('str_2', 64);
            $schema->datetime('created_at');
            $schema->string('str_3', 64);
            $schema->save(Handler::DO_ALL);
            $names[] = $name;
        }

        $handler = $this->bulkProvider();
        $bulk = $handler->getSchemas($names);

        foreach ($names as $name) {
            $this->assertSame(
                \array_keys($handler->getSchema($name)->getColumns()),
                \array_keys($bulk[$name]->getColumns()),
                "Column order of {$name} diverged between per-table and bulk introspection",
            );
        }
    }

    public function testReadonlyHandlerDelegatesBulkIntrospection(): void
    {
        $this->makeSampleSchema();

        $inner = $this->bulkProvider();
        $readonly = new ReadonlyHandler($inner);

        $bulk = $readonly->getSchemas(['authors', 'books']);

        $this->assertSame(['authors', 'books'], \array_keys($bulk));
        $this->assertTrue($bulk['authors']->exists());
        $this->assertSameAsInDB($inner->getSchema('authors'), $bulk['authors']);
    }

    public function testDatabaseGetSchemasWithExplicitList(): void
    {
        $this->makeSampleSchema();

        $schemas = $this->database->getSchemas(['authors', 'books']);

        $this->assertSame(['authors', 'books'], \array_keys($schemas));
        $this->assertTrue($schemas['authors']->exists());
        $this->assertTrue($schemas['books']->exists());
    }

    public function testDatabaseGetSchemasWithoutListReadsWholeDatabase(): void
    {
        $this->makeSampleSchema();

        $schemas = $this->database->getSchemas();

        $names = [];
        foreach ($schemas as $schema) {
            $this->assertTrue($schema->exists());
            $names[] = $schema->getName();
        }

        \sort($names);
        $this->assertSame(['authors', 'books', 'tags'], $names);
    }

    /**
     * Drivers that actually batch introspection queries opt in here.
     */
    protected function isBatchedProvider(): bool
    {
        return false;
    }

    protected function bulkProvider(): BulkSchemaProviderInterface
    {
        $handler = $this->database->getDriver()->getSchemaHandler();
        $this->assertInstanceOf(BulkSchemaProviderInterface::class, $handler);

        return $handler;
    }

    /**
     * @param non-empty-string[] $tables
     *
     * @return array{0: int, 1: QueryCounter}
     */
    protected function countBulkQueries(array $tables): array
    {
        $driver = $this->database->getDriver();
        $counter = new QueryCounter();
        $driver->setLogger($counter);

        try {
            $this->bulkProvider()->getSchemas($tables);
        } finally {
            $driver->setLogger(static::$logger);
        }

        return [$counter->count(), $counter];
    }

    /**
     * authors(id, name), books(id, title, status enum, author_id -> authors, index on title),
     * tags(id, label). Exercises columns, PK, index, FK, native/emulated enum and char columns.
     */
    protected function makeSampleSchema(): void
    {
        $authors = $this->schema('authors');
        $authors->primary('id');
        $authors->string('name', 64)->defaultValue('anonymous');
        $authors->save(Handler::DO_ALL);

        $tags = $this->schema('tags');
        $tags->primary('id');
        $tags->string('label', 32)->defaultValue('tag');
        $tags->save(Handler::DO_ALL);

        $books = $this->schema('books');
        $books->primary('id');
        $books->string('title', 128)->defaultValue('untitled');
        $books->enum('status', ['draft', 'published'])->defaultValue('draft');
        $books->integer('author_id')->nullable(true);
        $books->index(['title']);
        $books->foreignKey(['author_id'])->references('authors', ['id']);
        $books->save(Handler::DO_ALL);
    }
}
