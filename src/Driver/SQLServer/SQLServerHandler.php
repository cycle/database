<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\SQLServer\Schema\SQLServerColumn;
use Cycle\Database\Driver\SQLServer\Schema\SQLServerTable;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Schema\PrefetchedIntrospection;

class SQLServerHandler extends Handler
{
    /**
     * Maximum number of names bound into a single `IN (...)` list.
     */
    private const BULK_CHUNK = 1000;

    /**
     * @psalm-param non-empty-string $table
     */
    public function getSchema(string $table, ?string $prefix = null): AbstractTable
    {
        return new SQLServerTable($this->driver, $table, $prefix ?? '');
    }

    /**
     * Introspect a set of tables with a constant number of queries. The per-table `table_name = ?`
     * filters become `table_name IN (...)`, and the foreign keys — normally read with one
     * `sp_fkeys` call per table — are read for the whole set from `sys.foreign_keys` in a shape that
     * matches the `sp_fkeys` rows consumed by {@see \Cycle\Database\Driver\SQLServer\Schema\SQLServerForeignKey}.
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

        $targets = [];
        $names = [];
        $seen = [];
        foreach ($tables as $table) {
            $full = $prefix . $table;
            $targets[$table] = $full;
            if (!isset($seen[$full])) {
                $seen[$full] = true;
                $names[] = $full;
            }
        }

        $existing = $this->fetchBulkExisting($names);
        $columns = $this->groupByBulkTable($this->fetchBulkColumns($names));
        $indexes = $this->groupByBulkTable($this->fetchBulkIndexes($names));
        $primaryKeys = $this->groupPrimaryKeys($this->fetchBulkPrimaryKeys($names));
        $references = $this->groupReferences($this->fetchBulkReferences($names));

        // DEFAULT and CHECK constraints are keyed by database-wide unique ids (object id, and
        // parent object id + column id), so a single map is shared by all tables.
        $objectIds = $this->collectObjectIds($columns);
        $defaultConstraints = $this->fetchBulkDefaultConstraints($objectIds, $columns);
        $checkConstraints = $this->fetchBulkCheckConstraints($objectIds, $columns);

        $result = [];
        foreach ($targets as $table => $full) {
            $result[$table] = new SQLServerTable(
                $this->driver,
                $table,
                $prefix,
                new PrefetchedIntrospection(isset($existing[$full]), [
                    'columns' => $columns[$full] ?? [],
                    'defaultConstraints' => $defaultConstraints,
                    'checkConstraints' => $checkConstraints,
                    'indexes' => $indexes[$full] ?? [],
                    'primaryKeys' => $primaryKeys[$full] ?? [],
                    'references' => $references[$full] ?? [],
                ]),
            );
        }

        return $result;
    }

    public function getTableNames(string $prefix = ''): array
    {
        $query = "SELECT [table_name] FROM [information_schema].[tables] WHERE [table_type] = 'BASE TABLE'";

        $tables = [];
        foreach ($this->driver->query($query)->fetchAll(\PDO::FETCH_NUM) as $name) {
            if ($prefix !== '' && !\str_starts_with($name[0], $prefix)) {
                continue;
            }

            $tables[] = $name[0];
        }

        return $tables;
    }

    /**
     * @psalm-param non-empty-string $table
     */
    public function hasTable(string $table): bool
    {
        $query = "SELECT COUNT(*) FROM [information_schema].[tables]
            WHERE [table_type] = 'BASE TABLE' AND [table_name] = ?";

        return (bool) $this->driver->query($query, [$table])->fetchColumn();
    }

    public function eraseTable(AbstractTable $table): void
    {
        $this->driver->execute(
            "TRUNCATE TABLE {$this->driver->identifier($table->getFullName())}",
        );
    }

    /**
     * @psalm-param non-empty-string $table
     * @psalm-param non-empty-string $name
     */
    public function renameTable(string $table, string $name): void
    {
        $this->run(
            'sp_rename @objname = ?, @newname = ?',
            [$table, $name],
        );
    }

    public function createColumn(AbstractTable $table, AbstractColumn $column): void
    {
        $this->run(
            "ALTER TABLE {$this->identify($table)} ADD {$column->sqlStatement($this->driver)}",
        );
    }

    /**
     * Driver specific column alter command.
     *
     * @throws SchemaException
     */
    public function alterColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column,
    ): void {
        if (!$initial instanceof SQLServerColumn || !$column instanceof SQLServerColumn) {
            throw new SchemaException('SQlServer handler can work only with SQLServer columns');
        }

        //In SQLServer we have to drop ALL related indexes and foreign keys while
        //applying type change... yeah...

        $indexesBackup = [];
        $foreignBackup = [];
        foreach ($table->getIndexes() as $index) {
            if (\in_array($column->getName(), $index->getColumns(), true)) {
                $indexesBackup[] = $index;
                $this->dropIndex($table, $index);
            }
        }

        foreach ($table->getForeignKeys() as $foreign) {
            if ($column->getName() === $foreign->getColumns()) {
                $foreignBackup[] = $foreign;
                $this->dropForeignKey($table, $foreign);
            }
        }

        //Column will recreate needed constraints
        foreach ($column->getConstraints() as $constraint) {
            $this->dropConstrain($table, $constraint);
        }

        //Rename is separate operation
        if ($column->getName() !== $initial->getName()) {
            $this->renameColumn($table, $initial, $column);

            //This call is required to correctly built set of alter operations
            $initial->setName($column->getName());
        }

        foreach ($column->alterOperations($this->driver, $initial) as $operation) {
            $this->run("ALTER TABLE {$this->identify($table)} {$operation}");
        }

        //Restoring indexes and foreign keys
        foreach ($indexesBackup as $index) {
            $this->createIndex($table, $index);
        }

        foreach ($foreignBackup as $foreign) {
            $this->createForeignKey($table, $foreign);
        }
    }

    public function dropIndex(AbstractTable $table, AbstractIndex $index): void
    {
        $this->run("DROP INDEX {$this->identify($index)} ON {$this->identify($table)}");
    }

    public function enableForeignKeyConstraints(): void
    {
        foreach ($this->getTableNames() as $table) {
            $this->run("ALTER TABLE {$this->identify($table)} WITH CHECK CHECK CONSTRAINT ALL");
        }
    }

    public function disableForeignKeyConstraints(): void
    {
        foreach ($this->getTableNames() as $table) {
            $this->run("ALTER TABLE {$this->identify($table)} NOCHECK CONSTRAINT ALL");
        }
    }

    private function renameColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column,
    ): void {
        $this->run(
            "sp_rename ?, ?, 'COLUMN'",
            [
                $table->getFullName() . '.' . $initial->getName(),
                $column->getName(),
            ],
        );
    }

    /**
     * Run a query whose `WHERE` filters an `IN (...)` list, chunking the values so the bound
     * parameter count stays bounded.
     *
     * @param list<int|string> $values
     * @param callable(string):string $build Receives the `?, ?, ...` placeholder list.
     *
     * @return array<array-key, array>
     */
    private function fetchByValues(array $values, callable $build): array
    {
        if ($values === []) {
            return [];
        }

        $rows = [];
        foreach (\array_chunk($values, self::BULK_CHUNK) as $chunk) {
            $placeholders = \implode(', ', \array_fill(0, \count($chunk), '?'));

            foreach ($this->driver->query($build($placeholders), \array_values($chunk)) as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param list<string> $names
     *
     * @return array<string, true>
     */
    private function fetchBulkExisting(array $names): array
    {
        $rows = $this->fetchByValues($names, static fn(string $in): string => "SELECT [table_name] "
            . "FROM [information_schema].[tables] "
            . "WHERE [table_type] = 'BASE TABLE' AND [table_name] IN ({$in})");

        $result = [];
        foreach ($rows as $row) {
            $result[$row['table_name']] = true;
        }

        return $result;
    }

    /**
     * @param list<string> $names
     */
    private function fetchBulkColumns(array $names): array
    {
        return $this->fetchByValues($names, static fn(string $in): string => 'SELECT *, '
            . '[information_schema].[columns].[table_name] AS [bulkTable] '
            . 'FROM [information_schema].[columns] INNER JOIN [sys].[columns] AS [sysColumns] '
            . 'ON (object_name([object_id]) = [table_name] AND [sysColumns].[name] = [COLUMN_NAME]) '
            . "WHERE [table_name] IN ({$in}) "
            . 'ORDER BY [table_name], [ORDINAL_POSITION]');
    }

    /**
     * @param list<string> $names
     */
    private function fetchBulkIndexes(array $names): array
    {
        return $this->fetchByValues($names, static fn(string $in): string => 'SELECT [indexes].[name] AS [indexName], '
            . '[cl].[name] AS [columnName], [columns].[is_descending_key] AS [isDescendingKey], '
            . '[is_primary_key] AS [isPrimary], [is_unique] AS [isUnique], [t].[name] AS [bulkTable] '
            . 'FROM [sys].[indexes] AS [indexes] '
            . 'INNER JOIN [sys].[index_columns] as [columns] '
            . '  ON [indexes].[object_id] = [columns].[object_id] AND [indexes].[index_id] = [columns].[index_id] '
            . 'INNER JOIN [sys].[columns] AS [cl] '
            . '  ON [columns].[object_id] = [cl].[object_id] AND [columns].[column_id] = [cl].[column_id] '
            . 'INNER JOIN [sys].[tables] AS [t] '
            . '  ON [indexes].[object_id] = [t].[object_id] '
            . "WHERE [t].[name] IN ({$in}) AND [is_primary_key] = 0 "
            . 'ORDER BY [t].[name], [indexes].[name], [indexes].[index_id], [columns].[index_column_id]');
    }

    /**
     * @param list<string> $names
     */
    private function fetchBulkPrimaryKeys(array $names): array
    {
        return $this->fetchByValues($names, static fn(string $in): string => 'SELECT [indexes].[name] AS [indexName], '
            . '[cl].[name] AS [columnName], [t].[name] AS [bulkTable] '
            . 'FROM [sys].[indexes] AS [indexes] '
            . 'INNER JOIN [sys].[index_columns] as [columns] '
            . '  ON [indexes].[object_id] = [columns].[object_id] AND [indexes].[index_id] = [columns].[index_id] '
            . 'INNER JOIN [sys].[columns] AS [cl] '
            . '  ON [columns].[object_id] = [cl].[object_id] AND [columns].[column_id] = [cl].[column_id] '
            . 'INNER JOIN [sys].[tables] AS [t] '
            . '  ON [indexes].[object_id] = [t].[object_id] '
            . "WHERE [t].[name] IN ({$in}) AND [is_primary_key] = 1 "
            . 'ORDER BY [t].[name], [indexes].[name], [indexes].[index_id], [columns].[index_column_id]');
    }

    /**
     * Read the foreign keys of the whole set from `sys.foreign_keys`, reproducing the columns of the
     * `sp_fkeys` result consumed by {@see \Cycle\Database\Driver\SQLServer\Schema\SQLServerForeignKey}.
     * The referential rule is emitted with `sp_fkeys` semantics (`0` = CASCADE, non-zero = NO ACTION).
     *
     * @param list<string> $names
     */
    private function fetchBulkReferences(array $names): array
    {
        return $this->fetchByValues($names, static fn(string $in): string => 'SELECT [fk].[name] AS [FK_NAME], '
            . '[pt].[name] AS [FKTABLE_NAME], [fcol].[name] AS [FKCOLUMN_NAME], '
            . '[rt].[name] AS [PKTABLE_NAME], [rcol].[name] AS [PKCOLUMN_NAME], '
            . 'CASE WHEN [fk].[update_referential_action] = 1 THEN 0 ELSE 1 END AS [UPDATE_RULE], '
            . 'CASE WHEN [fk].[delete_referential_action] = 1 THEN 0 ELSE 1 END AS [DELETE_RULE] '
            . 'FROM [sys].[foreign_keys] AS [fk] '
            . 'INNER JOIN [sys].[foreign_key_columns] AS [fkc] ON [fkc].[constraint_object_id] = [fk].[object_id] '
            . 'INNER JOIN [sys].[tables] AS [pt] ON [pt].[object_id] = [fk].[parent_object_id] '
            . 'INNER JOIN [sys].[columns] AS [fcol] '
            . '  ON [fcol].[object_id] = [fkc].[parent_object_id] AND [fcol].[column_id] = [fkc].[parent_column_id] '
            . 'INNER JOIN [sys].[tables] AS [rt] ON [rt].[object_id] = [fk].[referenced_object_id] '
            . 'INNER JOIN [sys].[columns] AS [rcol] '
            . '  ON [rcol].[object_id] = [fkc].[referenced_object_id] '
            . '  AND [rcol].[column_id] = [fkc].[referenced_column_id] '
            . "WHERE [pt].[name] IN ({$in}) "
            . 'ORDER BY [pt].[name], [fk].[name], [fkc].[constraint_column_id]');
    }

    /**
     * Distinct object ids of every table in the fetched column rows.
     *
     * @param array<string, array> $columns Column rows grouped per table.
     *
     * @return list<int|string>
     */
    private function collectObjectIds(array $columns): array
    {
        $ids = [];
        foreach ($columns as $rows) {
            foreach ($rows as $row) {
                $ids[(string) $row['object_id']] = $row['object_id'];
            }
        }

        return \array_values($ids);
    }

    /**
     * @param list<int|string> $objectIds
     * @param array<string, array> $columns
     *
     * @return array<array-key, string>
     */
    private function fetchBulkDefaultConstraints(array $objectIds, array $columns): array
    {
        if (!$this->columnsHave($columns, static fn(array $c): bool => !empty($c['default_object_id']))) {
            return [];
        }

        $rows = $this->fetchByValues($objectIds, static fn(string $in): string => 'SELECT [object_id], [name] '
            . "FROM [sys].[default_constraints] WHERE [parent_object_id] IN ({$in})");

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['object_id']] = $row['name'];
        }

        return $result;
    }

    /**
     * @param list<int|string> $objectIds
     * @param array<string, array> $columns
     *
     * @return array<array-key, list<array>>
     */
    private function fetchBulkCheckConstraints(array $objectIds, array $columns): array
    {
        $required = $this->columnsHave(
            $columns,
            static fn(array $c): bool => $c['DATA_TYPE'] === 'varchar' && !empty($c['CHARACTER_MAXIMUM_LENGTH']),
        );

        if (!$required) {
            return [];
        }

        $rows = $this->fetchByValues($objectIds, static fn(string $in): string => 'SELECT '
            . 'object_definition([o].[object_id]) AS [definition], '
            . 'OBJECT_NAME([o].[object_id]) AS [name], [o].[parent_object_id] AS [parentId], [c].[colid] AS [colid] '
            . 'FROM [sys].[objects] AS [o] '
            . 'JOIN [sys].[sysconstraints] AS [c] ON [o].[object_id] = [c].[constid] '
            . "WHERE [type_desc] = 'CHECK_CONSTRAINT' AND [parent_object_id] IN ({$in})");

        $result = [];
        foreach ($rows as $row) {
            $result[$row['parentId'] . ':' . $row['colid']][] = $row;
        }

        return $result;
    }

    /**
     * @param array<string, array> $columns
     * @param callable(array):bool $predicate
     */
    private function columnsHave(array $columns, callable $predicate): bool
    {
        foreach ($columns as $rows) {
            foreach ($rows as $row) {
                if ($predicate($row)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Group raw rows per table using the `bulkTable` alias.
     *
     * @param array<array-key, array> $rows
     *
     * @return array<string, list<array>>
     */
    private function groupByBulkTable(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[$row['bulkTable']][] = $row;
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
            $result[$row['bulkTable']][] = $row['columnName'];
        }

        return $result;
    }

    /**
     * @param array<array-key, array> $rows
     *
     * @return array<string, list<array>>
     */
    private function groupReferences(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[$row['FKTABLE_NAME']][] = $row;
        }

        return $result;
    }
}
