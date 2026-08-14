<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use Cycle\Database\Schema\AbstractTable;

/**
 * Introspect a set of tables with a constant number of queries instead of one full introspection
 * per table.
 *
 * The list of tables is mandatory: there is deliberately no "no list means the whole database"
 * mode, so that the queries never read catalog rows of tables the caller did not ask for. The
 * "all tables of the database" scenario is an explicit path in
 * {@see \Cycle\Database\Database::getTables()} that first resolves the names via
 * {@see HandlerInterface::getTableNames()} and then passes them here.
 */
interface BulkSchemaProviderInterface
{
    /**
     * Introspect several tables at once.
     *
     * Existence is derived from presence in the batched result: a table that does not exist yet is
     * returned as an empty schema ({@see AbstractTable::exists()} is `false`), never skipped. For a
     * table that does exist the result is identical to {@see HandlerInterface::getSchema()}.
     *
     * @param non-empty-string[] $tables Table names WITHOUT the database prefix.
     * @param string|null $prefix Database specific table prefix applied to every table.
     *
     * @return array<non-empty-string, AbstractTable> Keyed by the input table name, in the input
     *         order.
     */
    public function getSchemas(array $tables, ?string $prefix = null): array;
}
