<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Database\Driver\Postgres\Schema\PostgresTable;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;

/**
 * @property PostgresDriver $driver
 */
class PostgresHandler extends Handler
{
    /**
     * @inheritDoc
     */
    public function getSchema(string $table, string $prefix = null): AbstractTable
    {
        return new PostgresTable($this->driver, $table, $prefix ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function getTableNames(string $prefix = ''): array
    {
        $query = "SELECT table_schema, table_name
            FROM information_schema.tables
            WHERE table_schema in ('" . implode("','", $this->driver->getTableSchema()) . "')
            AND table_type = 'BASE TABLE'";

        $tables = [];
        foreach ($this->driver->query($query) as $row) {
            if ($prefix !== '' && strpos($row['table_name'], $prefix) !== 0) {
                continue;
            }

            $tables[] = $row['table_schema'] . '.' . $row['table_name'];
        }

        return $tables;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable(string $table): bool
    {
        [$schema, $name] = $this->driver->parseSchemaAndTable($table);

        $query = "SELECT COUNT(table_name)
            FROM information_schema.tables
            WHERE table_schema = ?
            AND table_type = 'BASE TABLE'
            AND table_name = ?";

        return (bool)$this->driver->query($query, [$schema, $name])->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function eraseTable(AbstractTable $table): void
    {
        $this->driver->execute(
            "TRUNCATE TABLE {$this->driver->identifier($table->getFullName())}"
        );
    }

    /**
     * @inheritdoc
     */
    public function renameTable(string $table, string $name): void
    {
        // New table name should not contain a schema
        [$schema, $name] = $this->driver->parseSchemaAndTable($name);

        parent::renameTable($table, $name);
    }

    /**
     * {@inheritdoc}
     *
     * @throws SchemaException
     */
    public function alterColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column
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
        if (empty($operations)) {
            return;
        }

        //Postgres columns should be altered using set of operations
        $query = sprintf(
            'ALTER TABLE %s %s',
            $this->identify($table),
            trim(implode(', ', $operations), ', ')
        );

        $this->run($query);
    }

    /**
     * @inheritdoc
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
