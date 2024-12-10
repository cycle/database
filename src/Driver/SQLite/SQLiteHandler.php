<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLite;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\SQLite\Schema\SQLiteTable;
use Cycle\Database\Exception\DBALException;
use Cycle\Database\Exception\HandlerException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractTable;

class SQLiteHandler extends Handler
{
    /**
     * @return string[]
     */
    public function getTableNames(string $prefix = ''): array
    {
        $query = $this->driver->query(
            "SELECT name FROM 'sqlite_master' WHERE type = 'table'",
        );

        $tables = [];
        foreach ($query as $table) {
            if ($table['name'] === 'sqlite_sequence') {
                continue;
            }

            if ($prefix !== '' && !\str_starts_with($table['name'], $prefix)) {
                continue;
            }

            $tables[] = $table['name'];
        }

        return $tables;
    }

    /**
     * @psalm-param non-empty-string $table
     */
    public function hasTable(string $table): bool
    {
        $query = "SELECT COUNT('sql') FROM 'sqlite_master' WHERE type = 'table' and name = ?";

        return (bool) $this->driver->query($query, [$table])->fetchColumn();
    }

    public function getSchema(string $table, ?string $prefix = null): AbstractTable
    {
        return new SQLiteTable($this->driver, $table, $prefix ?? '');
    }

    public function eraseTable(AbstractTable $table): void
    {
        $this->driver->execute(
            "DELETE FROM {$this->driver->identifier($table->getFullName())}",
        );
    }

    public function syncTable(AbstractTable $table, int $operation = self::DO_ALL): void
    {
        if (!$this->requiresRebuild($table)) {
            //Nothing special, can be handled as usually
            parent::syncTable($table, $operation);

            return;
        }

        if ($table->getComparator()->isPrimaryChanged()) {
            throw new DBALException('Unable to change primary keys for existed table');
        }

        $initial = clone $table;
        $initial->resetState();

        //Temporary table is required to copy data over
        $temporary = $this->createTemporary($table);

        //Moving data over
        $this->copyData(
            $initial->getFullName(),
            $temporary->getFullName(),
            $this->createMapping($initial, $temporary),
        );

        //We can drop initial table now
        $this->dropTable($table);

        //Renaming temporary table (should automatically handle table renaming)
        $this->renameTable($temporary->getFullName(), $initial->getFullName());

        //Not all databases support adding index while table creation, so we can do it after
        foreach ($table->getIndexes() as $index) {
            $this->createIndex($table, $index);
        }
    }

    public function createColumn(AbstractTable $table, AbstractColumn $column): void
    {
        //Not supported
    }

    public function dropColumn(AbstractTable $table, AbstractColumn $column): void
    {
        //Not supported
    }

    public function alterColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column,
    ): void {
        //Not supported
    }

    public function createForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void
    {
        //Not supported
    }

    public function dropForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void
    {
        //Not supported
    }

    public function alterForeignKey(
        AbstractTable $table,
        AbstractForeignKey $initial,
        AbstractForeignKey $foreignKey,
    ): void {
        //Not supported
    }

    public function enableForeignKeyConstraints(): void
    {
        $this->run('PRAGMA foreign_keys = ON;');
    }

    public function disableForeignKeyConstraints(): void
    {
        $this->run('PRAGMA foreign_keys = OFF;');
    }

    /**
     * Temporary table based on parent.
     */
    protected function createTemporary(AbstractTable $table): AbstractTable
    {
        //Temporary table is required to copy data over
        $temporary = clone $table;
        $temporary->setName(
            'spiral_temp_' . $table->getFullName() . '_' . \uniqid(),
        );

        //We don't need any indexes in temporary table
        foreach ($temporary->getIndexes() as $index) {
            $temporary->dropIndex($index->getColumnsWithSort());
        }

        $this->createTable($temporary);

        return $temporary;
    }

    /**
     * Rebuild is required when columns or foreign keys are altered.
     */
    private function requiresRebuild(AbstractTable $table): bool
    {
        $comparator = $table->getComparator();

        $difference = [
            \count($comparator->addedColumns()),
            \count($comparator->droppedColumns()),
            \count($comparator->alteredColumns()),

            \count($comparator->addedForeignKeys()),
            \count($comparator->droppedForeignKeys()),
            \count($comparator->alteredForeignKeys()),
        ];

        return \array_sum($difference) !== 0;
    }

    /**
     * Copy table data to another location.
     *
     * @see http://stackoverflow.com/questions/4007014/alter-column-in-sqlite
     *
     * @psalm-param non-empty-string $source
     * @psalm-param non-empty-string $to
     *
     * @param array  $mapping (destination => source)
     *
     * @throws HandlerException
     */
    private function copyData(string $source, string $to, array $mapping): void
    {
        $sourceColumns = \array_keys($mapping);
        $targetColumns = \array_values($mapping);

        //Preparing mapping
        $sourceColumns = \array_map([$this, 'identify'], $sourceColumns);
        $targetColumns = \array_map([$this, 'identify'], $targetColumns);

        $query = \sprintf(
            'INSERT INTO %s (%s) SELECT %s FROM %s',
            $this->identify($to),
            \implode(', ', $targetColumns),
            \implode(', ', $sourceColumns),
            $this->identify($source),
        );

        $this->run($query);
    }

    /**
     * Get mapping between new and initial columns.
     */
    private function createMapping(AbstractTable $source, AbstractTable $target): array
    {
        $mapping = [];
        foreach ($target->getColumns() as $name => $column) {
            if ($source->hasColumn($name)) {
                $mapping[$name] = $column->getName();
            }
        }

        return $mapping;
    }
}
