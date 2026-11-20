<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLite\Schema;

use Cycle\Database\Tests\Feature\Common\Schema\IntrospectionQueryCountTest as CommonClass;
use Cycle\Database\Tests\Utils\QueryCounter;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::SQLite)]
final class IntrospectionQueryCountTest extends CommonClass
{
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
    #[Test]
    public function testIndexCountDoesNotAffectQueryCount(): void
    {
        $singleIndexes = 1;
        $manyIndexes = 5;

        $this->makeSampleTable('single', columns: 5, indexes: $singleIndexes, foreignKeys: 0);
        $this->makeSampleTable('many', columns: 5, indexes: $manyIndexes, foreignKeys: 0);

        [$single] = $this->countIntrospectionQueries('single');
        [$many] = $this->countIntrospectionQueries('many');

        Assert::same($many - $single, self::QUERIES_PER_INDEX * ($manyIndexes - $singleIndexes));
    }

    /**
     * Every foreign key implies an index over its columns, so this case degrades into the per-index
     * cost described in {@see testIndexCountDoesNotAffectQueryCount()}. The foreign keys themselves
     * are still read with a single `PRAGMA foreign_key_list`.
     */
    #[Test]
    public function testForeignKeyCountDoesNotAffectQueryCount(): void
    {
        $singleForeignKeys = 1;
        $manyForeignKeys = 5;

        $this->makeParents($manyForeignKeys);

        $this->makeSampleTable('single', columns: 2, indexes: 0, foreignKeys: $singleForeignKeys);
        $this->makeSampleTable('many', columns: 2, indexes: 0, foreignKeys: $manyForeignKeys);

        [$single, $singleQueries] = $this->countIntrospectionQueries('single');
        [$many, $manyQueries] = $this->countIntrospectionQueries('many');

        Assert::same($many - $single, self::QUERIES_PER_INDEX * ($manyForeignKeys - $singleForeignKeys));
        Assert::same($this->countMatching($singleQueries, 'foreign_key_list'), 1);
        Assert::same($this->countMatching($manyQueries, 'foreign_key_list'), 1);
    }

    /**
     * The sample table carries an index per every explicit index and foreign key.
     */
    protected function getIntrospectionQueryLimit(): int
    {
        return self::QUERIES_PER_TABLE
            + self::QUERIES_PER_INDEX * (self::WIDE_INDEXES + self::WIDE_FOREIGN_KEYS);
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
