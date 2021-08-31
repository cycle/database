<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL;

use PDO;
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
    public function getSchema(string $table, string $prefix = null): AbstractTable
    {
        return new MySQLTable($this->driver, $table, $prefix ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function getTableNames(string $prefix = ''): array
    {
        $result = [];
        foreach ($this->driver->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) as $row) {
            if ($prefix !== '' && strpos($row[0], $prefix) !== 0) {
                continue;
            }

            $result[] = $row[0];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable(string $name): bool
    {
        $query = 'SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = ? AND `table_name` = ?';

        return (bool)$this->driver->query(
            $query,
            [$this->driver->getSource(), $name]
        )->fetchColumn();
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
     * {@inheritdoc}
     */
    public function alterColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column
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
                    CHANGE {$this->identify($initial)} {$column->sqlStatement($this->driver)}"
        );

        //Restoring FKs
        foreach ($foreignBackup as $foreign) {
            $this->createForeignKey($table, $foreign);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dropIndex(AbstractTable $table, AbstractIndex $index): void
    {
        $this->run(
            "DROP INDEX {$this->identify($index)} ON {$this->identify($table)}"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function alterIndex(AbstractTable $table, AbstractIndex $initial, AbstractIndex $index): void
    {
        $this->run(
            "ALTER TABLE {$this->identify($table)}
                    DROP INDEX  {$this->identify($initial)},
                    ADD {$index->sqlStatement($this->driver, false)}"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function dropForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void
    {
        $this->run(
            "ALTER TABLE {$this->identify($table)} DROP FOREIGN KEY {$this->identify($foreignKey)}"
        );
    }

    /**
     * Get statement needed to create table.
     *
     * @param AbstractTable $table
     * @return string
     *
     * @throws SchemaException
     */
    protected function createStatement(AbstractTable $table)
    {
        if (!$table instanceof MySQLTable) {
            throw new SchemaException('MySQLHandler can process only MySQL tables');
        }

        return parent::createStatement($table) . " ENGINE {$table->getEngine()}";
    }

    /**
     * @param AbstractColumn $column
     *
     * @throws MySQLException
     */
    protected function assertValid(AbstractColumn $column): void
    {
        if (
            $column->getDefaultValue() !== null
            && in_array(
                $column->getAbstractType(),
                ['text', 'tinyText', 'longText', 'blob', 'tinyBlob', 'longBlob']
            )
        ) {
            throw new MySQLException(
                "Column {$column} of type text/blob can not have non empty default value"
            );
        }
    }
}
