<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\Postgres;

use Spiral\Database\Driver\Handler;
use Spiral\Database\Driver\Postgres\Schema\PostgresColumn;
use Spiral\Database\Exception\SchemaException;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractTable;

class PostgresHandler extends Handler
{
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
        if ($column->getName() != $initial->getName()) {
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
