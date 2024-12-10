<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\Postgres\Exception\PostgresException;
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
     * @psalm-param non-empty-string $table
     */
    public function getSchema(string $table, ?string $prefix = null): AbstractTable
    {
        return new PostgresTable($this->driver, $table, $prefix ?? '');
    }

    public function getTableNames(string $prefix = ''): array
    {
        $query = "SELECT table_schema, table_name
            FROM information_schema.tables
            WHERE table_type = 'BASE TABLE'";

        if ($this->driver->shouldUseDefinedSchemas()) {
            $query .= " AND table_schema in ('" . \implode("','", $this->driver->getSearchSchemas()) . "')";
        } else {
            $query .= " AND table_schema !~ '^pg_.*' AND table_schema != 'information_schema'";
        }

        $tables = [];
        foreach ($this->driver->query($query) as $row) {
            if ($prefix !== '' && !\str_starts_with($row['table_name'], $prefix)) {
                continue;
            }

            $tables[] = $row['table_schema'] . '.' . $row['table_name'];
        }

        return $tables;
    }

    /**
     * @psalm-param non-empty-string $table
     */
    public function hasTable(string $table): bool
    {
        [$schema, $name] = $this->driver->parseSchemaAndTable($table);

        $query = "SELECT COUNT(table_name)
            FROM information_schema.tables
            WHERE table_schema = ?
            AND table_type = 'BASE TABLE'
            AND table_name = ?";

        return (bool) $this->driver->query($query, [$schema, $name])->fetchColumn();
    }

    public function eraseTable(AbstractTable $table, bool $restartIdentity = false): void
    {
        $query = "TRUNCATE TABLE {$this->driver->identifier($table->getFullName())}";

        if ($restartIdentity) {
            $query .= ' RESTART IDENTITY CASCADE';
        }

        $this->driver->execute($query);
    }

    /**
     * @psalm-param non-empty-string $table
     * @psalm-param non-empty-string $name
     */
    public function renameTable(string $table, string $name): void
    {
        // New table name should not contain a schema
        [, $name] = $this->driver->parseSchemaAndTable($name);

        parent::renameTable($table, $name);
    }

    /**
     * @throws SchemaException
     */
    public function alterColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column,
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
        $query = \sprintf(
            'ALTER TABLE %s %s',
            $this->identify($table),
            \trim(\implode(', ', $operations), ', '),
        );

        $this->run($query);
    }

    public function enableForeignKeyConstraints(): void
    {
        $this->run('SET CONSTRAINTS ALL IMMEDIATE;');
    }

    public function disableForeignKeyConstraints(): void
    {
        $this->run('SET CONSTRAINTS ALL DEFERRED;');
    }

    /**
     * @psalm-param non-empty-string $statement
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
     * @throws PostgresException
     */
    protected function assertValid(AbstractColumn $column): void
    {
        if ($column->getDefaultValue() !== null && \in_array($column->getAbstractType(), ['json', 'jsonb'])) {
            try {
                \json_decode($column->getDefaultValue(), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                throw new PostgresException(
                    \sprintf('Column `%s` of type json/jsonb has an invalid default json value.', $column),
                );
            }
        }
    }

    private function renameColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column,
    ): void {
        $statement = \sprintf(
            'ALTER TABLE %s RENAME COLUMN %s TO %s',
            $this->identify($table),
            $this->identify($initial),
            $this->identify($column),
        );

        $this->run($statement);
    }
}
