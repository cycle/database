<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLite\Schema;

use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;

class SQLiteTable extends AbstractTable
{
    protected function fetchColumns(): array
    {
        /**
         * Parsing column definitions.
         */
        $definition = $this->driver->query(
            "SELECT sql FROM sqlite_master WHERE type = 'table' and name = ?",
            [$this->getFullName()],
        )->fetchColumn();

        /*
         * There is not really many ways to get extra information about column
         * in SQLite, let's parse table schema. As mention, Cycle SQLite
         * schema reader will support fully only tables created by Cycle as we
         * expecting every column definition be on new line.
         */
        $definition = \explode("\n", $definition);

        $result = [];
        foreach ($this->columnSchemas(['table' => $definition]) as $schema) {
            //Making new column instance
            $result[] = SQLiteColumn::createInstance(
                $this->getFullName(),
                $schema + [
                    'quoted'     => $this->driver->quote($schema['name']),
                    'identifier' => $this->driver->identifier($schema['name']),
                ],
                $this->driver->getTimezone(),
            );
        }

        return $result;
    }

    protected function fetchIndexes(): array
    {
        $primaryKeys = $this->fetchPrimaryKeys();
        $query = "PRAGMA index_list({$this->driver->quote($this->getFullName())})";

        $result = [];
        foreach ($this->driver->query($query) as $schema) {
            $index = SQLiteIndex::createInstance(
                $this->getFullName(),
                $schema,
                // 3+ format
                $this->driver->query(
                    "PRAGMA INDEX_XINFO({$this->driver->quote($schema['name'])})",
                )->fetchAll(),
                // legacy format
                $this->driver->query(
                    "PRAGMA INDEX_INFO({$this->driver->quote($schema['name'])})",
                )->fetchAll(),
            );

            if ($index->getColumns() === $primaryKeys) {
                // skip auto-generated index
                continue;
            }

            //Index schema and all related columns
            $result[] = $index;
        }

        return $result;
    }

    protected function fetchReferences(): array
    {
        $query = "PRAGMA foreign_key_list({$this->driver->quote($this->getFullName())})";

        // join keys together
        $fks = [];
        foreach ($this->driver->query($query) as $schema) {
            if (!isset($fks[$schema['id']])) {
                $fks[$schema['id']] = $schema;
                $fks[$schema['id']]['from'] = [$schema['from']];
                $fks[$schema['id']]['to'] = [$schema['to']];
                continue;
            }

            $fks[$schema['id']]['from'][] = $schema['from'];
            $fks[$schema['id']]['to'][] = $schema['to'];
        }

        $result = [];
        foreach ($fks as $schema) {
            $result[] = SQLiteForeignKey::createInstance(
                $this->getFullName(),
                $this->getPrefix(),
                $schema,
            );
        }

        return $result;
    }

    /**
     * Fetching primary keys from table.
     *
     */
    protected function fetchPrimaryKeys(): array
    {
        $primaryKeys = [];
        foreach ($this->columnSchemas() as $column) {
            if (!empty($column['pk'])) {
                $primaryKeys[] = $column['name'];
            }
        }

        return $primaryKeys;
    }

    protected function createColumn(string $name): AbstractColumn
    {
        return new SQLiteColumn($this->getFullName(), $name, $this->driver->getTimezone());
    }

    protected function createIndex(string $name): AbstractIndex
    {
        return new SQLiteIndex($this->getFullName(), $name);
    }

    protected function createForeign(string $name): AbstractForeignKey
    {
        return new SQLiteForeignKey($this->getFullName(), $this->getPrefix(), $name);
    }

    /**
     * @param array $include Include following parameters into each line.
     *
     */
    private function columnSchemas(array $include = []): array
    {
        $columns = $this->driver->query(
            'PRAGMA TABLE_INFO(' . $this->driver->quote($this->getFullName()) . ')',
        );

        $result = [];

        foreach ($columns as $column) {
            $result[] = $column + $include;
        }

        return $result;
    }
}
