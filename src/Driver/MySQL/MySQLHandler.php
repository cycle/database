<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL;

use PDO;
use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\MySQL\Exception\MySQLException;
use Cycle\Database\Driver\MySQL\Schema\MySQLTable;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;
use Spiral\Database\Schema\AbstractColumn as SpiralAbstractColumn;
use Spiral\Database\Schema\AbstractIndex as SpiralAbstractIndex;
use Spiral\Database\Schema\AbstractForeignKey as SpiralAbstractForeignKey;
use Spiral\Database\Schema\AbstractTable as SpiralAbstractTable;
use Spiral\Database\Driver\MySQL\MySQLHandler as SpiralMySQLHandler;

class_exists(SpiralAbstractColumn::class);
class_exists(SpiralAbstractIndex::class);
class_exists(SpiralAbstractForeignKey::class);
class_exists(SpiralAbstractTable::class);

class MySQLHandler extends Handler
{
    public function getSchema(string $table, string $prefix = null): AbstractTable
    {
        return new MySQLTable($this->driver, $table, $prefix ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function getTableNames(): array
    {
        $result = [];
        foreach ($this->driver->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) as $row) {
            $result[] = $row[0];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable(string $table): bool
    {
        $query = 'SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = ? AND `table_name` = ?';

        return (bool)$this->driver->query(
            $query,
            [$this->driver->getSource(), $table]
        )->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function eraseTable(SpiralAbstractTable $table): void
    {
        $this->driver->execute(
            "TRUNCATE TABLE {$this->driver->identifier($table->getName())}"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function alterColumn(
        SpiralAbstractTable $table,
        SpiralAbstractColumn $initial,
        SpiralAbstractColumn $column
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
    public function dropIndex(SpiralAbstractTable $table, SpiralAbstractIndex $index): void
    {
        $this->run(
            "DROP INDEX {$this->identify($index)} ON {$this->identify($table)}"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function alterIndex(SpiralAbstractTable $table, SpiralAbstractIndex $initial, SpiralAbstractIndex $index): void
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
    public function dropForeignKey(SpiralAbstractTable $table, SpiralAbstractForeignKey $foreignKey): void
    {
        $this->run(
            "ALTER TABLE {$this->identify($table)} DROP FOREIGN KEY {$this->identify($foreignKey)}"
        );
    }

    /**
     * Get statement needed to create table.
     *
     * @param AbstractTable $table
     *
     * @throws SchemaException
     *
     * @return string
     */
    protected function createStatement(SpiralAbstractTable $table)
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
    protected function assertValid(SpiralAbstractColumn $column): void
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
\class_alias(MySQLHandler::class, SpiralMySQLHandler::class, false);
