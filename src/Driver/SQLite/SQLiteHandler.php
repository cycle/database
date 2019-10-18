<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\SQLite;

use Spiral\Database\Driver\Handler;
use Spiral\Database\Exception\DBALException;
use Spiral\Database\Exception\HandlerException;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractForeignKey;
use Spiral\Database\Schema\AbstractTable;

/**
 * Handler provides ability to exectute non supported changes using temporary
 * tables and data mapping.
 */
class SQLiteHandler extends Handler
{
    /**
     * Drop table from database.
     *
     * @param AbstractTable $table
     *
     * @throws HandlerException
     */
    public function dropTable(AbstractTable $table): void
    {
        parent::dropTable($table);
    }

    /**
     * {@inheritdoc}
     */
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
            $initial->getName(),
            $temporary->getName(),
            $this->createMapping($initial, $temporary)
        );

        //We can drop initial table now
        $this->dropTable($table);

        //Renaming temporary table (should automatically handle table renaming)
        $this->renameTable($temporary->getName(), $initial->getName());

        //Not all databases support adding index while table creation, so we can do it after
        foreach ($table->getIndexes() as $index) {
            $this->createIndex($table, $index);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createColumn(AbstractTable $table, AbstractColumn $column): void
    {
        //Not supported
    }

    /**
     * {@inheritdoc}
     */
    public function dropColumn(AbstractTable $table, AbstractColumn $column): void
    {
        //Not supported
    }

    /**
     * {@inheritdoc}
     */
    public function alterColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column
    ): void {
        //Not supported
    }

    /**
     * {@inheritdoc}
     */
    public function createForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void
    {
        //Not supported
    }

    /**
     * {@inheritdoc}
     */
    public function dropForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void
    {
        //Not supported
    }

    /**
     * {@inheritdoc}
     */
    public function alterForeignKey(
        AbstractTable $table,
        AbstractForeignKey $initial,
        AbstractForeignKey $foreignKey
    ): void {
        //Not supported
    }

    /**
     * Temporary table based on parent.
     *
     * @param AbstractTable $table
     * @return AbstractTable
     */
    protected function createTemporary(AbstractTable $table): AbstractTable
    {
        //Temporary table is required to copy data over
        $temporary = clone $table;
        $temporary->setName('spiral_temp_' . $table->getName() . '_' . uniqid());

        //We don't need any indexes in temporary table
        foreach ($temporary->getIndexes() as $index) {
            $temporary->dropIndex($index->getColumns());
        }

        $this->createTable($temporary);

        return $temporary;
    }

    /**
     * Rebuild is required when columns or foreign keys are altered.
     *
     * @param AbstractTable $table
     * @return bool
     */
    private function requiresRebuild(AbstractTable $table): bool
    {
        $comparator = $table->getComparator();

        $difference = [
            count($comparator->addedColumns()),
            count($comparator->droppedColumns()),
            count($comparator->alteredColumns()),

            count($comparator->addedForeignKeys()),
            count($comparator->droppedForeignKeys()),
            count($comparator->alteredForeignKeys()),
        ];

        return array_sum($difference) != 0;
    }

    /**
     * Copy table data to another location.
     *
     * @see http://stackoverflow.com/questions/4007014/alter-column-in-sqlite
     *
     * @param string $source
     * @param string $to
     * @param array  $mapping (destination => source)
     *
     * @throws HandlerException
     */
    private function copyData(string $source, string $to, array $mapping): void
    {
        $sourceColumns = array_keys($mapping);
        $targetColumns = array_values($mapping);

        //Preparing mapping
        $sourceColumns = array_map([$this, 'identify'], $sourceColumns);
        $targetColumns = array_map([$this, 'identify'], $targetColumns);

        $query = sprintf(
            'INSERT INTO %s (%s) SELECT %s FROM %s',
            $this->identify($to),
            implode(', ', $targetColumns),
            implode(', ', $sourceColumns),
            $this->identify($source)
        );

        $this->run($query);
    }

    /**
     * Get mapping between new and initial columns.
     *
     * @param AbstractTable $source
     * @param AbstractTable $target
     * @return array
     */
    private function createMapping(AbstractTable $source, AbstractTable $target)
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
