<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\MySQL\Exception\MySQLException;
use Cycle\Database\Driver\MySQL\Schema\MySQLTable;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;

class MySQLHandler extends Handler
{
    /**
     * @psalm-param non-empty-string $table
     */
    public function getSchema(string $table, ?string $prefix = null): AbstractTable
    {
        return new MySQLTable($this->driver, $table, $prefix ?? '');
    }

    public function getTableNames(string $prefix = ''): array
    {
        $result = [];
        foreach ($this->driver->query('SHOW TABLES')->fetchAll(\PDO::FETCH_NUM) as $row) {
            if ($prefix !== '' && !\str_starts_with($row[0], $prefix)) {
                continue;
            }

            $result[] = $row[0];
        }

        return $result;
    }

    /**
     * @psalm-param non-empty-string $table
     */
    public function hasTable(string $table): bool
    {
        $query = 'SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = ? AND `table_name` = ?';

        return (bool) $this->driver->query(
            $query,
            [$this->driver->getSource(), $table],
        )->fetchColumn();
    }

    public function eraseTable(AbstractTable $table): void
    {
        $this->driver->execute(
            "TRUNCATE TABLE {$this->driver->identifier($table->getFullName())}",
        );
    }

    public function alterColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column,
    ): void {
        $foreignBackup = [];
        foreach ($table->getForeignKeys() as $foreign) {
            if ($column->getName() === $foreign->getColumns()) {
                $foreignBackup[] = $foreign;
                $this->dropForeignKey($table, $foreign);
            }
        }

        $this->run(
            "ALTER TABLE {$this->identify($table)}
                    CHANGE {$this->identify($initial)} {$column->sqlStatement($this->driver)}",
        );

        //Restoring FKs
        foreach ($foreignBackup as $foreign) {
            $this->createForeignKey($table, $foreign);
        }
    }

    public function dropIndex(AbstractTable $table, AbstractIndex $index): void
    {
        $this->run(
            "DROP INDEX {$this->identify($index)} ON {$this->identify($table)}",
        );
    }

    public function alterIndex(AbstractTable $table, AbstractIndex $initial, AbstractIndex $index): void
    {
        $this->run(
            "ALTER TABLE {$this->identify($table)}
                    DROP INDEX  {$this->identify($initial)},
                    ADD {$index->sqlStatement($this->driver, false)}",
        );
    }

    public function dropForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void
    {
        $this->run(
            "ALTER TABLE {$this->identify($table)} DROP FOREIGN KEY {$this->identify($foreignKey)}",
        );
    }

    public function enableForeignKeyConstraints(): void
    {
        $this->run('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function disableForeignKeyConstraints(): void
    {
        $this->run('SET FOREIGN_KEY_CHECKS=0;');
    }

    /**
     * Get statement needed to create table.
     *
     * @throws SchemaException
     */
    protected function createStatement(AbstractTable $table): string
    {
        $table instanceof MySQLTable or throw new SchemaException('MySQLHandler can process only MySQL tables');

        return parent::createStatement($table) . " ENGINE {$table->getEngine()}";
    }

    /**
     * @throws MySQLException
     */
    protected function assertValid(AbstractColumn $column): void
    {
        if (
            $column->getDefaultValue() !== null
            && \in_array(
                $column->getAbstractType(),
                ['text', 'tinyText', 'longText', 'blob', 'tinyBlob', 'longBlob', 'json'],
            )
        ) {
            throw new MySQLException(
                "Column {$column} of type text/blob/json can not have non empty default value",
            );
        }
    }
}
