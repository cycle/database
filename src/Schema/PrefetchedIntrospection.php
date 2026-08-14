<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Schema;

/**
 * Carries pre-fetched raw introspection rows of a single table so that {@see AbstractTable} can
 * build its schema without issuing any per-table queries.
 *
 * The bulk introspection path ({@see \Cycle\Database\Driver\BulkSchemaProviderInterface::getSchemas()})
 * runs a handful of batched queries for the whole set of tables, groups the raw rows per table and
 * feeds each group here. The driver {@see AbstractTable} reads the buckets it needs from {@see $data}
 * instead of querying the database, so the resulting schema is identical to the per-table path by
 * construction.
 *
 * @internal
 */
final class PrefetchedIntrospection
{
    /**
     * @param bool $exists Whether the table is present in the database. In the bulk path existence
     *        is derived from the table being returned by the introspection queries, so it replaces
     *        the per-table {@see \Cycle\Database\Driver\HandlerInterface::hasTable()} call.
     * @param array<string, mixed> $data Driver-defined buckets of raw rows for this table.
     */
    public function __construct(
        public readonly bool $exists,
        public readonly array $data,
    ) {}
}
