<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\MySQL;

use Spiral\Database\Driver\Handler;
use Spiral\Database\Driver\MySQL\Exception\MySQLException;
use Spiral\Database\Driver\MySQL\Schema\MySQLTable;
use Spiral\Database\Exception\SchemaException;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractForeignKey;
use Spiral\Database\Schema\AbstractIndex;
use Spiral\Database\Schema\AbstractTable;

class MySQLHandler extends Handler
{
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
            if ($column->getName() == $foreign->getColumns()) {
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
        $this->run("DROP INDEX {$this->identify($index)} ON {$this->identify($table)}");
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
        $this->run("ALTER TABLE {$this->identify($table)} DROP FOREIGN KEY {$this->identify($foreignKey)}");
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
            in_array(
                $column->getAbstractType(),
                ['text', 'tinyText', 'longText', 'blob', 'tinyBlob', 'longBlob']
            )
            && is_string($column->getDefaultValue())
            && $column->getDefaultValue() !== ''
        ) {
            throw new MySQLException(
                "Column {$column} of type text/blob can not have non empty default value"
            );
        }
    }
}
