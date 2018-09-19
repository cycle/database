<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Driver\SQLite;

use Spiral\Database\Driver\AbstractHandler;
use Spiral\Database\Exception\DBALException;
use Spiral\Database\Exception\SchemaHandlerException;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractReference;
use Spiral\Database\Schema\AbstractTable;

/**
 * Handler provides ability to exectute non supported changes using temporary
 * tables and data mapping.
 */
class SQLiteHandler extends AbstractHandler
{
    /**
     * Drop table from database.
     *
     * @param AbstractTable $table
     *
     * @throws SchemaHandlerException
     */
    public function dropTable(AbstractTable $table)
    {
        parent::dropTable($table);
    }

    /**
     * {@inheritdoc}
     */
    public function syncTable(AbstractTable $table, int $behaviour = self::DO_ALL)
    {
        if (!$this->requiresRebuild($table)) {
            //Nothing special, can be handled as usually
            parent::syncTable($table, $behaviour);

            return;
        }

        if ($table->getComparator()->isPrimaryChanged()) {
            throw new DBALException("Unable to change primary keys for existed table");
        }

        //Now we have to work with temporary table in order to perform every change
        $this->log('Rebuilding table {table} to apply required modifications.', [
            'table' => $this->identify($table)
        ]);

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
    public function createColumn(AbstractTable $table, AbstractColumn $column)
    {
        //Not supported
    }

    /**
     * {@inheritdoc}
     */
    public function dropColumn(AbstractTable $table, AbstractColumn $column)
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
    ) {
        //Not supported
    }

    /**
     * {@inheritdoc}
     */
    public function createForeign(AbstractTable $table, AbstractReference $foreign)
    {
        //Not supported
    }

    /**
     * {@inheritdoc}
     */
    public function dropForeign(AbstractTable $table, AbstractReference $foreign)
    {
        //Not supported
    }

    /**
     * {@inheritdoc}
     */
    public function alterForeign(
        AbstractTable $table,
        AbstractReference $initial,
        AbstractReference $foreign
    ) {
        //Not supported
    }

    /**
     * Rebuild is required when columns or foreign keys are altered.
     *
     * @param AbstractTable $table
     *
     * @return bool
     */
    private function requiresRebuild(AbstractTable $table): bool
    {
        $comparator = $table->getComparator();

        $difference = [
            count($comparator->addedColumns()),
            count($comparator->droppedColumns()),
            count($comparator->alteredColumns()),

            count($comparator->addedForeigns()),
            count($comparator->droppedForeigns()),
            count($comparator->alteredForeigns()),
        ];

        return array_sum($difference) != 0;
    }

    /**
     * Temporary table based on parent.
     *
     * @param AbstractTable $table
     *
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
     * Copy table data to another location.
     *
     * @see http://stackoverflow.com/questions/4007014/alter-column-in-sqlite
     *
     * @param string $source
     * @param string $to
     * @param array  $mapping (destination => source)
     *
     * @throws SchemaHandlerException
     */
    private function copyData(string $source, string $to, array $mapping)
    {
        $sourceColumns = array_keys($mapping);
        $targetColumns = array_values($mapping);

        $this->log(
            'Copying table data from {source} to {to} using mapping ({columns}) => ({target}).',
            [
                'source'  => $this->identify($source),
                'to'      => $this->identify($to),
                'columns' => implode(', ', $sourceColumns),
                'target'  => implode(', ', $targetColumns),
            ]
        );

        //Preparing mapping
        $sourceColumns = array_map([$this, 'identify'], $sourceColumns);
        $targetColumns = array_map([$this, 'identify'], $targetColumns);

        $query = \Spiral\interpolate('INSERT INTO {to} ({target}) SELECT {columns} FROM {source}',
            [
                'source'  => $this->identify($source),
                'to'      => $this->identify($to),
                'columns' => implode(', ', $sourceColumns),
                'target'  => implode(', ', $targetColumns),
            ]
        );

        $this->run($query);
    }

    /**
     * Get mapping between new and initial columns.
     *
     * @param AbstractTable $source
     * @param AbstractTable $target
     *
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
