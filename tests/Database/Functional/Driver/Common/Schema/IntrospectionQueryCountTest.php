<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;
use Cycle\Database\Tests\Utils\QueryCounter;

/**
 * Introspection of a single table must cost a constant number of queries: it should not grow with
 * the number of columns, indexes or foreign keys of that table.
 */
abstract class IntrospectionQueryCountTest extends BaseTest
{
    /**
     * Shape of the sample table used by {@see testWideTableIntrospectionIsCheap()}. Driver
     * specific overrides of {@see getIntrospectionQueryLimit()} derive their limits from it.
     */
    protected const WIDE_COLUMNS = 40;

    protected const WIDE_INDEXES = 3;
    protected const WIDE_FOREIGN_KEYS = 1;

    private QueryCounter $counter;

    public function testColumnCountDoesNotAffectQueryCount(): void
    {
        $this->makeParents(1);

        $this->makeTable('narrow', columns: 2, indexes: 1, foreignKeys: 1);
        $this->makeTable('wide', columns: 40, indexes: 1, foreignKeys: 1);

        $narrow = $this->countIntrospectionQueries('narrow');
        $wide = $this->countIntrospectionQueries('wide');

        $this->assertSame(
            $narrow[0],
            $wide[0],
            \sprintf(
                "Introspection of a 40 column table took %d queries instead of %d.\n\nNarrow:\n%s\n\nWide:\n%s",
                $wide[0],
                $narrow[0],
                $narrow[1],
                $wide[1],
            ),
        );
    }

    public function testForeignKeyCountDoesNotAffectQueryCount(): void
    {
        $this->makeParents(5);

        $this->makeTable('single', columns: 2, indexes: 0, foreignKeys: 1);
        $this->makeTable('many', columns: 2, indexes: 0, foreignKeys: 5);

        $single = $this->countIntrospectionQueries('single');
        $many = $this->countIntrospectionQueries('many');

        $this->assertSame(
            $single[0],
            $many[0],
            \sprintf(
                "Introspection of a table with 5 foreign keys took %d queries instead of %d.\n\n"
                . "Single:\n%s\n\nMany:\n%s",
                $many[0],
                $single[0],
                $single[1],
                $many[1],
            ),
        );
    }

    public function testIndexCountDoesNotAffectQueryCount(): void
    {
        $this->makeTable('single', columns: 5, indexes: 1, foreignKeys: 0);
        $this->makeTable('many', columns: 5, indexes: 5, foreignKeys: 0);

        $single = $this->countIntrospectionQueries('single');
        $many = $this->countIntrospectionQueries('many');

        $this->assertSame(
            $single[0],
            $many[0],
            \sprintf(
                "Introspection of a table with 5 indexes took %d queries instead of %d.\n\n"
                . "Single:\n%s\n\nMany:\n%s",
                $many[0],
                $single[0],
                $single[1],
                $many[1],
            ),
        );
    }

    /**
     * Wide table introspection must not degrade into dozens of round trips even in absolute numbers.
     */
    public function testWideTableIntrospectionIsCheap(): void
    {
        $this->makeParents(static::WIDE_FOREIGN_KEYS);
        $this->makeTable(
            'wide',
            columns: static::WIDE_COLUMNS,
            indexes: static::WIDE_INDEXES,
            foreignKeys: static::WIDE_FOREIGN_KEYS,
        );

        [$count, $queries] = $this->countIntrospectionQueries('wide');

        $this->assertLessThanOrEqual(
            $this->getIntrospectionQueryLimit(),
            $count,
            "Too many introspection queries:\n{$queries}",
        );
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->counter = new QueryCounter();
    }

    public function tearDown(): void
    {
        $this->database->getDriver()->setLogger(static::$logger);

        parent::tearDown();
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

        $this->counter->reset();
        $driver->setLogger($this->counter);

        try {
            $schema = $this->schema($table);
            $this->assertTrue($schema->exists());
        } finally {
            $driver->setLogger(static::$logger);
        }

        return [$this->counter->count(), clone $this->counter];
    }

    protected function makeParents(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $schema = $this->schema("parent_{$i}");
            $schema->primary('id');
            $schema->save(Handler::DO_ALL);
        }
    }

    protected function makeTable(string $name, int $columns, int $indexes, int $foreignKeys): AbstractTable
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
