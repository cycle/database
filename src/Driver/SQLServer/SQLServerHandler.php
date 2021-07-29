<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer;

use PDO;
use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\SQLServer\Schema\SQLServerColumn;
use Cycle\Database\Driver\SQLServer\Schema\SQLServerTable;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;

class SQLServerHandler extends Handler
{
    /**
     * @inheritDoc
     */
    public function getSchema(string $table, string $prefix = null): AbstractTable
    {
        return new SQLServerTable($this->driver, $table, $prefix ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function getTableNames(): array
    {
        $query = "SELECT [table_name] FROM [information_schema].[tables] WHERE [table_type] = 'BASE TABLE'";

        $tables = [];
        foreach ($this->driver->query($query)->fetchAll(PDO::FETCH_NUM) as $name) {
            $tables[] = $name[0];
        }

        return $tables;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable(string $name): bool
    {
        $query = "SELECT COUNT(*) FROM [information_schema].[tables]
            WHERE [table_type] = 'BASE TABLE' AND [table_name] = ?";

        return (bool)$this->driver->query($query, [$name])->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function eraseTable(AbstractTable $table): void
    {
        $this->driver->execute(
            "TRUNCATE TABLE {$this->driver->identifier($table->getName())}"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renameTable(string $table, string $name): void
    {
        $this->run(
            'sp_rename @objname = ?, @newname = ?',
            [$table, $name]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createColumn(AbstractTable $table, AbstractColumn $column): void
    {
        $this->run(
            "ALTER TABLE {$this->identify($table)} ADD {$column->sqlStatement($this->driver)}"
        );
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
            if (in_array($column->getName(), $index->getColumns(), true)) {
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
