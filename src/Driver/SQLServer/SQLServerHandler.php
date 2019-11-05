<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\SQLServer;

use Spiral\Database\Driver\Handler;
use Spiral\Database\Driver\SQLServer\Schema\SQLServerColumn;
use Spiral\Database\Exception\SchemaException;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractIndex;
use Spiral\Database\Schema\AbstractTable;

class SQLServerHandler extends Handler
{
    /**
     * {@inheritdoc}
     */
    public function renameTable(string $table, string $name): void
    {
        $this->run('sp_rename @objname = ?, @newname = ?', [$table, $name]);
    }

    /**
     * {@inheritdoc}
     */
    public function createColumn(AbstractTable $table, AbstractColumn $column): void
    {
        $this->run("ALTER TABLE {$this->identify($table)} ADD {$column->sqlStatement($this->driver)}");
    }

    /**
     * Driver specific column alter command.
     *
     * @param AbstractTable  $table
     * @param AbstractColumn $initial
     * @param AbstractColumn $column
     *
     * @throws SchemaException
     */
    public function alterColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column
    ): void {
        if (!$initial instanceof SQLServerColumn || !$column instanceof SQLServerColumn) {
            throw new SchemaException('SQlServer handler can work only with SQLServer columns');
        }

        //In SQLServer we have to drop ALL related indexes and foreign keys while
        //applying type change... yeah...

        $indexesBackup = [];
        $foreignBackup = [];
        foreach ($table->getIndexes() as $index) {
            if (in_array($column->getName(), $index->getColumns())) {
                $indexesBackup[] = $index;
                $this->dropIndex($table, $index);
            }
        }

        foreach ($table->getForeignKeys() as $foreign) {
            if ($column->getName() == $foreign->getColumns()) {
                $foreignBackup[] = $foreign;
                $this->dropForeignKey($table, $foreign);
            }
        }

        //Column will recreate needed constraints
        foreach ($column->getConstraints() as $constraint) {
            $this->dropConstrain($table, $constraint);
        }

        //Rename is separate operation
        if ($column->getName() != $initial->getName()) {
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

    /**
     * {@inheritdoc}
     */
    public function dropIndex(AbstractTable $table, AbstractIndex $index): void
    {
        $this->run("DROP INDEX {$this->identify($index)} ON {$this->identify($table)}");
    }

    /**
     * {@inheritdoc}
     */
    private function renameColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column
    ): void {
        $this->run(
            "sp_rename ?, ?, 'COLUMN'",
            [
                $table->getName() . '.' . $initial->getName(),
                $column->getName()
            ]
        );
    }
}
