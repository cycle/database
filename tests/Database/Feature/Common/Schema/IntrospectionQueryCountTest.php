<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Tests\Utils\QueryCounter;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

/**
 * Introspection of a single table must cost a constant number of queries: it should not grow with
 * the number of columns, indexes or foreign keys of that table.
 */
#[ExceptDriver(Driver::SQLite)]
class IntrospectionQueryCountTest extends DatabaseTestCase
{
    /**
     * Shape of the sample table used by {@see testWideTableIntrospectionIsCheap()}. Driver
     * specific overrides of {@see getIntrospectionQueryLimit()} derive their limits from it.
     */
    protected const WIDE_COLUMNS = 40;

    protected const WIDE_INDEXES = 3;
    protected const WIDE_FOREIGN_KEYS = 1;

    #[Test]
    public function testColumnCountDoesNotAffectQueryCount(): void
    {
        $this->makeParents(1);

        $this->makeSampleTable('narrow', columns: 2, indexes: 1, foreignKeys: 1);
        $this->makeSampleTable('wide', columns: 40, indexes: 1, foreignKeys: 1);

        $narrow = $this->countIntrospectionQueries('narrow');
        $wide = $this->countIntrospectionQueries('wide');

        Assert::same($wide[0], $narrow[0], \sprintf(
            "Introspection of a 40 column table took %d queries instead of %d.\n\nNarrow:\n%s\n\nWide:\n%s",
            $wide[0],
            $narrow[0],
            $narrow[1],
            $wide[1],
        ));
    }

    #[Test]
    public function testForeignKeyCountDoesNotAffectQueryCount(): void
    {
        $this->makeParents(5);

        $this->makeSampleTable('single', columns: 2, indexes: 0, foreignKeys: 1);
        $this->makeSampleTable('many', columns: 2, indexes: 0, foreignKeys: 5);

        $single = $this->countIntrospectionQueries('single');
        $many = $this->countIntrospectionQueries('many');

        Assert::same($many[0], $single[0], \sprintf(
            "Introspection of a table with 5 foreign keys took %d queries instead of %d.\n\n"
            . "Single:\n%s\n\nMany:\n%s",
            $many[0],
            $single[0],
            $single[1],
            $many[1],
        ));
    }

    #[Test]
    public function testIndexCountDoesNotAffectQueryCount(): void
    {
        $this->makeSampleTable('single', columns: 5, indexes: 1, foreignKeys: 0);
        $this->makeSampleTable('many', columns: 5, indexes: 5, foreignKeys: 0);

        $single = $this->countIntrospectionQueries('single');
        $many = $this->countIntrospectionQueries('many');

        Assert::same($many[0], $single[0], \sprintf(
            "Introspection of a table with 5 indexes took %d queries instead of %d.\n\n"
            . "Single:\n%s\n\nMany:\n%s",
            $many[0],
            $single[0],
            $single[1],
            $many[1],
        ));
    }

    /**
     * Wide table introspection must not degrade into dozens of round trips even in absolute numbers.
     */
    #[Test]
    public function testWideTableIntrospectionIsCheap(): void
    {
        $this->makeParents(static::WIDE_FOREIGN_KEYS);
        $this->makeSampleTable(
            'wide',
            columns: static::WIDE_COLUMNS,
            indexes: static::WIDE_INDEXES,
            foreignKeys: static::WIDE_FOREIGN_KEYS,
        );

        [$count, $queries] = $this->countIntrospectionQueries('wide');

        Assert::numeric($count)->lessThanOrEqual($this->getIntrospectionQueryLimit(), "Too many introspection queries:\n{$queries}");
    }

    /**
     * Maximum number of queries a single table introspection is allowed to make.
     */
    protected function getIntrospectionQueryLimit(): int
    {
        return 10;
    }

    /**
     * @return array{0: int, 1: QueryCounter}
     */
    protected function countIntrospectionQueries(string $table): array
    {
        $driver = $this->database->getDriver();

        $counter = new QueryCounter();
        $driver->setLogger($counter);

        try {
            $schema = $this->schema($table);
            Assert::true($schema->exists());
        } finally {
            $driver->setLogger($this->context->queryLog());
        }

        return [$counter->count(), $counter];
    }

    protected function makeParents(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $schema = $this->schema("parent_{$i}");
            $schema->primary('id');
            $schema->save(Handler::DO_ALL);
        }
    }

    protected function makeSampleTable(string $name, int $columns, int $indexes, int $foreignKeys): AbstractTable
    {
        $schema = $this->schema($name);
        $schema->primary('id');

        // `string` with a size and a default value is the worst case: it triggers both the CHECK
        // constraint lookup (emulated enums) and the DEFAULT constraint lookup.
        for ($i = 0; $i < $columns; $i++) {
            $schema->string("column_{$i}", 64)->defaultValue("value_{$i}");
        }

        // A native enum is resolved separately from the emulated ones.
        $schema->enum('status', ['active', 'disabled'])->defaultValue('active');

        for ($i = 0; $i < $indexes; $i++) {
            $schema->integer("indexed_{$i}")->defaultValue(0);
            $schema->index(["indexed_{$i}"]);
        }

        for ($i = 0; $i < $foreignKeys; $i++) {
            $schema->integer("parent_{$i}_id")->nullable(true);
            $schema->foreignKey(["parent_{$i}_id"])->references("parent_{$i}", ['id']);
        }

        $schema->save(Handler::DO_ALL);

        return $schema;
    }
}
