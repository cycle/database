<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\IntrospectionQueryCountTest as CommonClass;
use Cycle\Database\Tests\Utils\QueryCounter;

/**
 * @group driver
 * @group driver-sqlite
 */
class IntrospectionQueryCountTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    /**
     * Every index is read with a pair of `PRAGMA INDEX_XINFO` / `PRAGMA INDEX_INFO` calls,
     * see {@see \Cycle\Database\Driver\SQLite\Schema\SQLiteTable::fetchIndexes()}.
     */
    private const QUERIES_PER_INDEX = 2;

    /**
     * Queries a table introspection costs on its own: `hasTable`, `sqlite_master`, `TABLE_INFO`,
     * `index_list` and `foreign_key_list`.
     */
    private const QUERIES_PER_TABLE = 5;

    /**
     * SQLite reads every index with a pair of PRAGMA calls. It is an embedded engine without
     * network round trips, so the per-index cost is acceptable, and the growth is asserted
     * to be exactly linear instead of constant.
     */
    public function testIndexCountDoesNotAffectQueryCount(): void
    {
        $singleIndexes = 1;
        $manyIndexes = 5;

        $this->makeTable('single', columns: 5, indexes: $singleIndexes, foreignKeys: 0);
        $this->makeTable('many', columns: 5, indexes: $manyIndexes, foreignKeys: 0);

        [$single] = $this->countIntrospectionQueries('single');
        [$many] = $this->countIntrospectionQueries('many');

        $this->assertSame(self::QUERIES_PER_INDEX * ($manyIndexes - $singleIndexes), $many - $single);
    }

    /**
     * Every foreign key implies an index over its columns, so this case degrades into the per-index
     * cost described in {@see testIndexCountDoesNotAffectQueryCount()}. The foreign keys themselves
     * are still read with a single `PRAGMA foreign_key_list`.
     */
    public function testForeignKeyCountDoesNotAffectQueryCount(): void
    {
        $singleForeignKeys = 1;
        $manyForeignKeys = 5;

        $this->makeParents($manyForeignKeys);

        $this->makeTable('single', columns: 2, indexes: 0, foreignKeys: $singleForeignKeys);
        $this->makeTable('many', columns: 2, indexes: 0, foreignKeys: $manyForeignKeys);

        [$single, $singleQueries] = $this->countIntrospectionQueries('single');
        [$many, $manyQueries] = $this->countIntrospectionQueries('many');

        $this->assertSame(
            self::QUERIES_PER_INDEX * ($manyForeignKeys - $singleForeignKeys),
            $many - $single,
        );
        $this->assertSame(1, $this->countMatching($singleQueries, 'foreign_key_list'));
        $this->assertSame(1, $this->countMatching($manyQueries, 'foreign_key_list'));
    }

    /**
     * The sample table carries an index per every explicit index and foreign key.
     */
    protected function getIntrospectionQueryLimit(): int
    {
        return self::QUERIES_PER_TABLE
            + self::QUERIES_PER_INDEX * (static::WIDE_INDEXES + static::WIDE_FOREIGN_KEYS);
    }

    private function countMatching(QueryCounter $counter, string $needle): int
    {
        $count = 0;
        foreach ($counter->getQueries() as $query) {
            if (\str_contains($query, $needle)) {
                $count++;
            }
        }

        return $count;
    }
}
