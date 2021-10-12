<?php

declare(strict_types=1);

namespace Cycle\Database\Driver\Oracle;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\Oracle\Schema\OracleColumn;
use Cycle\Database\Driver\Oracle\Schema\OracleTable;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;

class OracleHandler extends Handler
{
    public function getTableNames(string $prefix = ''): array
    {
        $query = "SELECT TABLE_NAME, TABLESPACE_NAME
            FROM ALL_TABLES
            WHERE TABLESPACE_NAME in ('" . implode("','", $this->driver->getSearchSchemas()) . "')";

        $tables = [];
        foreach ($this->driver->query($query) as $row) {
            if ($prefix !== '' && strpos($row['TABLE_NAME'], $prefix) !== 0) {
                continue;
            }

            $tables[] = $row['TABLESPACE_NAME'] . '.' . $row['TABLE_NAME'];
        }

        return $tables;
    }

    public function hasTable(string $table): bool
    {
        [$schema, $name] = $this->driver->parseSchemaAndTable($table);

        $query = "SELECT COUNT(TABLE_NAME)
            FROM ALL_TABLES
            WHERE TABLESPACE_NAME = ?
            AND TABLE_NAME = ?";

        return (bool)$this->driver->query($query, [$schema, $name])->fetchColumn();
    }

    public function getSchema(string $table, string $prefix = null): AbstractTable
    {
        return new OracleTable($this->driver, $table, $prefix ?? '');
    }

    public function eraseTable(AbstractTable $table): void
    {
        // TODO: Implement eraseTable() method.
    }

    public function alterColumn(AbstractTable $table, AbstractColumn $initial, AbstractColumn $column): void
    {
        if (!$initial instanceof OracleColumn || !$column instanceof OracleColumn) {
            throw new SchemaException('Oracle handler can work only with Oracle columns');
        }

        //Rename is separate operation
        if ($column->getName() !== $initial->getName()) {
            $this->renameColumn($table, $initial, $column);

            //This call is required to correctly built set of alter operations
            $initial->setName($column->getName());
        }

        //Oracle columns should be altered using set of operations
        $operations = $column->alterOperations($this->driver, $initial);
        if (empty($operations)) {
            return;
        }

        //Oracle columns should be altered using set of operations
        $query = sprintf(
            'ALTER TABLE %s %s',
            $this->identify($table),
            trim(implode(', ', $operations), ', ')
        );

        $this->run($query);
    }

    /**
     * @param AbstractTable  $table
     * @param AbstractColumn $initial
     * @param AbstractColumn $column
     */
    private function renameColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column
    ): void {
        $statement = sprintf(
            'ALTER TABLE %s RENAME COLUMN %s TO %s',
            $this->identify($table),
            $this->identify($initial),
            $this->identify($column)
        );

        $this->run($statement);
    }
}
