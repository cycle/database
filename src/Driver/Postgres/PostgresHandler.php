<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\Postgres\Exception\PostgresException;
use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Database\Driver\Postgres\Schema\PostgresTable;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Schema\PrefetchedIntrospection;

/**
 * @property PostgresDriver $driver
 */
class PostgresHandler extends Handler
{
    /**
     * Maximum number of (schema, table) pairs bound into a single `IN (...)` list. Postgres allows
     * far more, but chunking keeps the parameter count bounded on any set size.
     */
    private const BULK_CHUNK = 1000;

    /**
     * @psalm-param non-empty-string $table
     */
    public function getSchema(string $table, ?string $prefix = null): AbstractTable
    {
        return new PostgresTable($this->driver, $table, $prefix ?? '');
    }

    /**
     * Introspect a set of tables with a constant number of queries. Every query is the per-table
     * one widened from `table_schema = ? AND table_name = ?` to a row-value `IN (...)` list, so the
     * rows fed to {@see PostgresTable} are identical to the per-table path.
     *
     * @param non-empty-string[] $tables
     *
     * @return array<non-empty-string, AbstractTable>
     */
    #[\Override]
    public function getSchemas(array $tables, ?string $prefix = null): array
    {
        if ($tables === []) {
            return [];
        }

        $prefix ??= '';

        // Resolve every requested table to its (schema, prefixed name) pair — exactly the way
        // PostgresTable does, so that the bucket keys line up with PostgresTable::getFullName().
        $targets = [];
        $pairs = [];
        $seen = [];
        foreach ($tables as $table) {
            [$schema, $name] = $this->driver->parseSchemaAndTable($table);
            $prefixed = $prefix . $name;
            $full = $schema . '.' . $prefixed;

            $targets[$table] = $full;
            if (!isset($seen[$full])) {
                $seen[$full] = true;
                $pairs[] = [$schema, $prefixed];
            }
        }

        $existing = $this->fetchBulkExisting($pairs);
        $columns = $this->groupRows($this->fetchBulkColumns($pairs), 'table_schema', 'table_name');
        $primaryKeys = $this->groupPrimaryKeys($this->fetchBulkPrimaryKeys($pairs));
        $indexRows = $this->groupRows($this->fetchBulkIndexRows($pairs), 'schemaname', 'tablename');
        $references = $this->groupRows($this->fetchBulkReferences($pairs), 'table_schema', 'table_name');

        // CHECK constraints are only relevant for tables that carry a char column with a size (the
        // same guard as the per-table path), so we scope the query to those tables.
        $checkConstraints = $this->groupCheckConstraints(
            $this->fetchBulkCheckConstraints($this->constrainedPairs($columns)),
        );

        // Native enum ranges are looked up by `<schema>.<type>`, so a single database-wide map is
        // shared by every table.
        $enumValues = $this->fetchBulkEnumValues($columns);

        $result = [];
        foreach ($targets as $table => $full) {
            $result[$table] = new PostgresTable(
                $this->driver,
                $table,
                $prefix,
                new PrefetchedIntrospection(isset($existing[$full]), [
                    'columns' => $columns[$full] ?? [],
                    'primaryKeys' => $primaryKeys[$full] ?? [],
                    'indexRows' => $indexRows[$full] ?? [],
                    'references' => $references[$full] ?? [],
                    'checkConstraints' => $checkConstraints[$full] ?? [],
                    'enumValues' => $enumValues,
                ]),
            );
        }

        return $result;
    }

    public function getTableNames(string $prefix = ''): array
    {
        $query = "SELECT table_schema, table_name
            FROM information_schema.tables
            WHERE table_type = 'BASE TABLE'";

        if ($this->driver->shouldUseDefinedSchemas()) {
            $query .= " AND table_schema in ('" . \implode("','", $this->driver->getSearchSchemas()) . "')";
        } else {
            $query .= " AND table_schema !~ '^pg_.*' AND table_schema != 'information_schema'";
        }

        $tables = [];
        foreach ($this->driver->query($query) as $row) {
            if ($prefix !== '' && !\str_starts_with($row['table_name'], $prefix)) {
                continue;
            }

            $tables[] = $row['table_schema'] . '.' . $row['table_name'];
        }

        return $tables;
    }

    /**
     * @psalm-param non-empty-string $table
     */
    public function hasTable(string $table): bool
    {
        [$schema, $name] = $this->driver->parseSchemaAndTable($table);

        $query = "SELECT COUNT(table_name)
            FROM information_schema.tables
            WHERE table_schema = ?
            AND table_type = 'BASE TABLE'
            AND table_name = ?";

        return (bool) $this->driver->query($query, [$schema, $name])->fetchColumn();
    }

    public function eraseTable(AbstractTable $table, bool $restartIdentity = false): void
    {
        $query = "TRUNCATE TABLE {$this->driver->identifier($table->getFullName())}";

        if ($restartIdentity) {
            $query .= ' RESTART IDENTITY CASCADE';
        }

        $this->driver->execute($query);
    }

    /**
     * @psalm-param non-empty-string $table
     * @psalm-param non-empty-string $name
     */
    public function renameTable(string $table, string $name): void
    {
        // New table name should not contain a schema
        [, $name] = $this->driver->parseSchemaAndTable($name);

        parent::renameTable($table, $name);
    }

    /**
     * @throws SchemaException
     */
    public function alterColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column,
    ): void {
        if (!$initial instanceof PostgresColumn || !$column instanceof PostgresColumn) {
            throw new SchemaException('Postgres handler can work only with Postgres columns');
        }

        //Rename is separate operation
        if ($column->getName() !== $initial->getName()) {
            $this->renameColumn($table, $initial, $column);

            //This call is required to correctly built set of alter operations
            $initial->setName($column->getName());
        }

        //Postgres columns should be altered using set of operations
        $operations = $column->alterOperations($this->driver, $initial);
        if (\count($operations) > 0) {
            //Postgres columns should be altered using set of operations
            $query = \sprintf(
                'ALTER TABLE %s %s',
                $this->identify($table),
                \trim(\implode(', ', $operations), ', '),
            );

            $this->run($query);
        }

        $operation = $column->commentOperation($this->driver, $initial);
        if ($operation !== null) {
            $this->run($operation);
        }
    }

    public function enableForeignKeyConstraints(): void
    {
        $this->run('SET CONSTRAINTS ALL IMMEDIATE;');
    }

    public function disableForeignKeyConstraints(): void
    {
        $this->run('SET CONSTRAINTS ALL DEFERRED;');
    }

    public function createTable(AbstractTable $table): void
    {
        if (!$table instanceof PostgresTable) {
            throw new SchemaException('Postgres handler can work only with Postgres table');
        }

        parent::createTable($table);

        foreach ($table->getColumns() as $column) {
            $this->createComment($column);
        }
    }

    public function createComment(PostgresColumn $column): void
    {
        if ($column->getComment() !== '') {
            $this->run($column->createComment($this->driver));
        }
    }

    /**
     * @psalm-param non-empty-string $statement
     */
    protected function run(string $statement, array $parameters = []): int
    {
        if ($this->driver instanceof PostgresDriver) {
            // invaliding primary key cache
            $this->driver->resetPrimaryKeys();
        }

        return parent::run($statement, $parameters);
    }

    /**
     * @throws PostgresException
     */
    protected function assertValid(AbstractColumn $column): void
    {
        if ($column->getDefaultValue() !== null && \in_array($column->getAbstractType(), ['json', 'jsonb'])) {
            try {
                \json_decode($column->getDefaultValue(), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                throw new PostgresException(
                    \sprintf('Column `%s` of type json/jsonb has an invalid default json value.', $column),
                );
            }
        }
    }

    private function renameColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column,
    ): void {
        $statement = \sprintf(
            'ALTER TABLE %s RENAME COLUMN %s TO %s',
            $this->identify($table),
            $this->identify($initial),
            $this->identify($column),
        );

        $this->run($statement);
    }

    /**
     * Run a query whose `WHERE` filters a row-value `IN (...)` list of (schema, table) pairs. The
     * pair list is chunked so the bound parameter count stays bounded, and the resulting rows are
     * concatenated.
     *
     * @param array<array{0: string, 1: string}> $pairs
     * @param callable(string):string $build Receives the `(?, ?), ...` placeholder list and returns
     *        the full SQL statement.
     *
     * @return array<array-key, array>
     */
    private function fetchByPairs(array $pairs, callable $build): array
    {
        if ($pairs === []) {
            return [];
        }

        $rows = [];
        foreach (\array_chunk($pairs, self::BULK_CHUNK) as $chunk) {
            $placeholders = \implode(', ', \array_fill(0, \count($chunk), '(?, ?)'));
            $parameters = \array_merge(...$chunk);

            foreach ($this->driver->query($build($placeholders), $parameters) as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Full names of the tables that actually exist. Replaces the per-table {@see hasTable()} call
     * and matches its semantics (`information_schema.tables`, `BASE TABLE`).
     *
     * @param array<array{0: string, 1: string}> $pairs
     *
     * @return array<string, true>
     */
    private function fetchBulkExisting(array $pairs): array
    {
        $rows = $this->fetchByPairs($pairs, static fn(string $in): string => <<<SQL
            SELECT table_schema, table_name
            FROM information_schema.tables
            WHERE table_type = 'BASE TABLE' AND (table_schema, table_name) IN ({$in})
            SQL);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['table_schema'] . '.' . $row['table_name']] = true;
        }

        return $result;
    }

    /**
     * @param array<array{0: string, 1: string}> $pairs
     */
    private function fetchBulkColumns(array $pairs): array
    {
        return $this->fetchByPairs($pairs, static fn(string $in): string => <<<SQL
            SELECT columns.*, pg_type.*, pg_description.description
            FROM information_schema.columns
            JOIN pg_catalog.pg_type
                ON (pg_type.typname = columns.udt_name)
            JOIN pg_catalog.pg_statio_all_tables
                ON (pg_statio_all_tables.relname = columns.table_name
                AND pg_statio_all_tables.schemaname = columns.table_schema)
            LEFT JOIN pg_catalog.pg_description
                ON (pg_description.objoid = pg_statio_all_tables.relid
                AND pg_description.objsubid = columns.ordinal_position)
            WHERE (columns.table_schema, columns.table_name) IN ({$in})
            ORDER BY columns.table_schema, columns.table_name, columns.ordinal_position
            SQL);
    }

    /**
     * @param array<array{0: string, 1: string}> $pairs
     */
    private function fetchBulkPrimaryKeys(array $pairs): array
    {
        return $this->fetchByPairs($pairs, static fn(string $in): string => <<<SQL
            SELECT table_constraints.table_schema, table_constraints.table_name, key_column_usage.column_name
            FROM information_schema.table_constraints
            JOIN information_schema.key_column_usage
                ON (
                    key_column_usage.table_name = table_constraints.table_name AND
                    key_column_usage.table_schema = table_constraints.table_schema AND
                    key_column_usage.constraint_name = table_constraints.constraint_name
                )
            WHERE table_constraints.constraint_type = 'PRIMARY KEY' AND
                  key_column_usage.ordinal_position IS NOT NULL AND
                  (table_constraints.table_schema, table_constraints.table_name) IN ({$in})
            ORDER BY table_constraints.table_schema, table_constraints.table_name, key_column_usage.ordinal_position
            SQL);
    }

    /**
     * @param array<array{0: string, 1: string}> $pairs
     */
    private function fetchBulkIndexRows(array $pairs): array
    {
        return $this->fetchByPairs($pairs, static fn(string $in): string => <<<SQL
            SELECT i.schemaname, i.tablename, i.indexname, i.indexdef, c.contype
            FROM pg_indexes i
            LEFT JOIN pg_namespace ns
                ON nspname = i.schemaname
            LEFT JOIN pg_constraint c
                ON c.conname = i.indexname
                AND c.connamespace = ns.oid
            WHERE (i.schemaname, i.tablename) IN ({$in})
            ORDER BY i.schemaname, i.tablename, i.indexname
            SQL);
    }

    /**
     * @param array<array{0: string, 1: string}> $pairs
     */
    private function fetchBulkReferences(array $pairs): array
    {
        return $this->fetchByPairs($pairs, static fn(string $in): string => <<<SQL
            SELECT tc.table_schema, tc.constraint_name, tc.constraint_schema, tc.table_name,
                   kcu.column_name, rc.update_rule, rc.delete_rule,
                   ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name
            JOIN information_schema.constraint_column_usage AS ccu
                ON ccu.constraint_name = tc.constraint_name
            JOIN information_schema.referential_constraints AS rc
                ON rc.constraint_name = tc.constraint_name
            WHERE tc.constraint_type = 'FOREIGN KEY' AND (tc.table_schema, tc.table_name) IN ({$in})
            ORDER BY tc.table_schema, tc.table_name, tc.constraint_name, kcu.ordinal_position
            SQL);
    }

    /**
     * Fetch all single-column CHECK constraints of the given tables (used to detect emulated
     * enums), grouped exactly the way {@see PostgresTable} expects: per table, keyed by the textual
     * `conkey`.
     *
     * @param array<array{0: string, 1: string}> $pairs
     */
    private function fetchBulkCheckConstraints(array $pairs): array
    {
        return $this->fetchByPairs($pairs, static fn(string $in): string => <<<SQL
            SELECT ns.nspname, cl.relname, c.conname, c.conkey, pg_get_constraintdef(c.oid) as consrc
            FROM pg_constraint c
            JOIN pg_class cl
                ON cl.oid = c.conrelid
            JOIN pg_namespace ns
                ON ns.oid = cl.relnamespace
            WHERE c.contype = 'c' AND (ns.nspname, cl.relname) IN ({$in})
            SQL);
    }

    /**
     * Build the database-wide native enum range map from the already fetched column rows.
     *
     * @param array<string, array> $columns Column rows grouped per table.
     *
     * @return array<string, list<string>> Keyed as `<schema>.<type>`.
     */
    private function fetchBulkEnumValues(array $columns): array
    {
        $types = [];
        foreach ($columns as $rows) {
            foreach ($rows as $schema) {
                if ($schema['data_type'] === 'USER-DEFINED' && $schema['typtype'] === 'e') {
                    $types[$schema['udt_schema'] . '.' . $schema['udt_name']] = [
                        $schema['udt_schema'],
                        $schema['udt_name'],
                    ];
                }
            }
        }

        $rows = $this->fetchByPairs(\array_values($types), static fn(string $in): string => <<<SQL
            SELECT ns.nspname, t.typname, e.enumlabel
            FROM pg_enum e
            JOIN pg_type t
                ON t.oid = e.enumtypid
            JOIN pg_namespace ns
                ON ns.oid = t.typnamespace
            WHERE (ns.nspname, t.typname) IN ({$in})
            ORDER BY e.enumsortorder
            SQL);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['nspname'] . '.' . $row['typname']][] = $row['enumlabel'];
        }

        return $result;
    }

    /**
     * (schema, table) pairs of the tables that carry a char column with a size — the only ones for
     * which CHECK constraints must be resolved.
     *
     * @param array<string, array> $columns Column rows grouped per table (`<schema>.<table>`).
     *
     * @return array<array{0: string, 1: string}>
     */
    private function constrainedPairs(array $columns): array
    {
        $pairs = [];
        foreach ($columns as $full => $rows) {
            foreach ($rows as $schema) {
                if (
                    $schema['character_maximum_length'] !== null
                    && \str_contains((string) $schema['data_type'], 'char')
                ) {
                    [$s, $t] = \explode('.', (string) $full, 2);
                    $pairs[] = [$s, $t];
                    break;
                }
            }
        }

        return $pairs;
    }

    /**
     * Group raw rows per table into `<schema>.<table> => rows[]`.
     *
     * @param array<array-key, array> $rows
     *
     * @return array<string, list<array>>
     */
    private function groupRows(array $rows, string $schemaKey, string $tableKey): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[$row[$schemaKey] . '.' . $row[$tableKey]][] = $row;
        }

        return $result;
    }

    /**
     * @param array<array-key, array> $rows
     *
     * @return array<string, list<string>>
     */
    private function groupPrimaryKeys(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[$row['table_schema'] . '.' . $row['table_name']][] = $row['column_name'];
        }

        return $result;
    }

    /**
     * Group CHECK constraint rows per table and, within a table, by the textual `conkey`.
     *
     * @param array<array-key, array> $rows
     *
     * @return array<string, array<string, list<array>>>
     */
    private function groupCheckConstraints(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[$row['nspname'] . '.' . $row['relname']][(string) $row['conkey']][] = $row;
        }

        return $result;
    }
}
