<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer\Schema;

use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;

class SQLServerTable extends AbstractTable
{
    /**
     * {@inheritDoc}
     *
     * SQLServer will reload schemas after successful savw.
     */
    #[\Override]
    public function save(int $operation = HandlerInterface::DO_ALL, bool $reset = true): void
    {
        parent::save($operation, $reset);

        if ($reset) {
            foreach ($this->fetchColumns() as $column) {
                $currentColumn = $this->current->findColumn($column->getName());
                if (!empty($currentColumn) && $column->compare($currentColumn)) {
                    //SQLServer is going to add some automatic constrains, let's handle them
                    $this->current->registerColumn($column);
                }
            }
        }
    }

    #[\Override]
    protected function fetchColumns(): array
    {
        $query = 'SELECT * FROM [information_schema].[columns] INNER JOIN [sys].[columns] AS [sysColumns] '
            . 'ON (object_name([object_id]) = [table_name] AND [sysColumns].[name] = [COLUMN_NAME]) '
            . 'WHERE [table_name] = ?';

        $schemas = $this->driver->query($query, [$this->getFullName()])->fetchAll();

        if ($schemas === []) {
            return [];
        }

        // The queries above are not scoped by the table schema, so the rows may belong to several
        // same-named tables from different schemas. Constraints are batched per object to keep
        // the resolution correct for every row.
        $objectIds = [];
        foreach ($schemas as $schema) {
            $objectIds[(string) $schema['object_id']] = $schema['object_id'];
        }
        $objectIds = \array_values($objectIds);

        $defaultConstraints = $this->fetchDefaultConstraints($objectIds, $schemas);
        $checkConstraints = $this->fetchCheckConstraints($objectIds, $schemas);

        $result = [];
        foreach ($schemas as $schema) {
            //Column initialization needs driver to properly resolve enum type
            $result[] = SQLServerColumn::createInstance(
                $this->getFullName(),
                $schema,
                $this->driver,
                $defaultConstraints,
                $checkConstraints,
            );
        }

        return $result;
    }

    #[\Override]
    protected function fetchIndexes(): array
    {
        $query = 'SELECT [indexes].[name] AS [indexName], '
            . '[cl].[name] AS [columnName], [columns].[is_descending_key] AS [isDescendingKey], '
            . "[is_primary_key] AS [isPrimary], [is_unique] AS [isUnique]\n"
            . "FROM [sys].[indexes] AS [indexes]\n"
            . "INNER JOIN [sys].[index_columns] as [columns]\n"
            . "  ON [indexes].[object_id] = [columns].[object_id] AND [indexes].[index_id] = [columns].[index_id]\n"
            . "INNER JOIN [sys].[columns] AS [cl]\n"
            . "  ON [columns].[object_id] = [cl].[object_id] AND [columns].[column_id] = [cl].[column_id]\n"
            . "INNER JOIN [sys].[tables] AS [t]\n"
            . "  ON [indexes].[object_id] = [t].[object_id]\n"
            . "WHERE [t].[name] = ? AND [is_primary_key] = 0  \n"
            . 'ORDER BY [indexes].[name], [indexes].[index_id], [columns].[index_column_id]';

        $result = $indexes = [];
        foreach ($this->driver->query($query, [$this->getFullName()]) as $index) {
            //Collecting schemas first
            $indexes[$index['indexName']][] = $index;
        }

        foreach ($indexes as $_ => $schema) {
            //Once all columns are aggregated we can finally create an index
            $result[] = SQLServerIndex::createInstance($this->getFullName(), $schema);
        }

        return $result;
    }

    #[\Override]
    protected function fetchReferences(): array
    {
        $query = $this->driver->query('sp_fkeys @fktable_name = ?', [$this->getFullName()]);

        // join keys together
        $fks = [];
        foreach ($query as $schema) {
            if (!isset($fks[$schema['FK_NAME']])) {
                $fks[$schema['FK_NAME']] = $schema;
                $fks[$schema['FK_NAME']]['PKCOLUMN_NAME'] = [$schema['PKCOLUMN_NAME']];
                $fks[$schema['FK_NAME']]['FKCOLUMN_NAME'] = [$schema['FKCOLUMN_NAME']];
                continue;
            }

            $fks[$schema['FK_NAME']]['PKCOLUMN_NAME'][] = $schema['PKCOLUMN_NAME'];
            $fks[$schema['FK_NAME']]['FKCOLUMN_NAME'][] = $schema['FKCOLUMN_NAME'];
        }

        $result = [];
        foreach ($fks as $schema) {
            $result[] = SQlServerForeignKey::createInstance(
                $this->getFullName(),
                $this->getPrefix(),
                $schema,
            );
        }

        return $result;
    }

    #[\Override]
    protected function fetchPrimaryKeys(): array
    {
        $query = "SELECT [indexes].[name] AS [indexName], [cl].[name] AS [columnName]\n"
            . "FROM [sys].[indexes] AS [indexes]\n"
            . "INNER JOIN [sys].[index_columns] as [columns]\n"
            . "  ON [indexes].[object_id] = [columns].[object_id] AND [indexes].[index_id] = [columns].[index_id]\n"
            . "INNER JOIN [sys].[columns] AS [cl]\n"
            . "  ON [columns].[object_id] = [cl].[object_id] AND [columns].[column_id] = [cl].[column_id]\n"
            . "INNER JOIN [sys].[tables] AS [t]\n"
            . "  ON [indexes].[object_id] = [t].[object_id]\n"
            . "WHERE [t].[name] = ? AND [is_primary_key] = 1 ORDER BY [indexes].[name], \n"
            . ' [indexes].[index_id], [columns].[index_column_id]';

        $result = [];
        foreach ($this->driver->query($query, [$this->getFullName()]) as $schema) {
            $result[] = $schema['columnName'];
        }

        return $result;
    }

    #[\Override]
    protected function createColumn(string $name): AbstractColumn
    {
        return new SQLServerColumn($this->getFullName(), $name, $this->driver->getTimezone());
    }

    #[\Override]
    protected function createIndex(string $name): AbstractIndex
    {
        return new SQLServerIndex($this->getFullName(), $name);
    }

    #[\Override]
    protected function createForeign(string $name): AbstractForeignKey
    {
        return new SQlServerForeignKey($this->getFullName(), $this->getPrefix(), $name);
    }

    /**
     * Fetch names of all DEFAULT constraints of the given tables at once, keyed by the constraint
     * object id (it is unique database-wide).
     *
     * @return array<array-key, string>
     */
    private function fetchDefaultConstraints(array $objectIds, array $schemas): array
    {
        $required = false;
        foreach ($schemas as $schema) {
            if (!empty($schema['default_object_id'])) {
                $required = true;
                break;
            }
        }

        if (!$required) {
            return [];
        }

        $placeholders = \implode(', ', \array_fill(0, \count($objectIds), '?'));
        $query = "SELECT [object_id], [name] FROM [sys].[default_constraints]
            WHERE [parent_object_id] IN ({$placeholders})";

        $result = [];
        foreach ($this->driver->query($query, $objectIds) as $constraint) {
            $result[(string) $constraint['object_id']] = $constraint['name'];
        }

        return $result;
    }

    /**
     * Fetch all CHECK constraints of the given tables at once (they are used to detect emulated
     * enums), keyed as `<table object id>:<column id>` — the column id alone is ambiguous when
     * the rows belong to more than one table.
     *
     * @return array<array-key, list<array>>
     */
    private function fetchCheckConstraints(array $objectIds, array $schemas): array
    {
        $required = false;
        foreach ($schemas as $schema) {
            if ($schema['DATA_TYPE'] === 'varchar' && !empty($schema['CHARACTER_MAXIMUM_LENGTH'])) {
                $required = true;
                break;
            }
        }

        if (!$required) {
            return [];
        }

        $placeholders = \implode(', ', \array_fill(0, \count($objectIds), '?'));
        $query = 'SELECT object_definition([o].[object_id]) AS [definition], '
            . "OBJECT_NAME([o].[object_id]) AS [name], [o].[parent_object_id] AS [parentId], [c].[colid] AS [colid]\n"
            . "FROM [sys].[objects] AS [o]\n"
            . "JOIN [sys].[sysconstraints] AS [c] ON [o].[object_id] = [c].[constid]\n"
            . "WHERE [type_desc] = 'CHECK_CONSTRAINT' AND [parent_object_id] IN ({$placeholders})";

        $result = [];
        foreach ($this->driver->query($query, $objectIds) as $constraint) {
            $result[$constraint['parentId'] . ':' . $constraint['colid']][] = $constraint;
        }

        return $result;
    }
}
