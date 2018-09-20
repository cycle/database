<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Driver\MySQL;

use Spiral\Database\Driver\AbstractHandler;
use Spiral\Database\Driver\MySQL\Schema\MySQLTable;
use Spiral\Database\Exception\Driver\MySQLDriverException;
use Spiral\Database\Exception\SchemaException;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractIndex;
use Spiral\Database\Schema\AbstractReference;
use Spiral\Database\Schema\AbstractTable;

class MySQLHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    public function alterColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column
    ) {

        $foreignBackup = [];
        foreach ($table->getReferences() as $foreign) {
            if ($column->getName() == $foreign->getColumn()) {
                $foreignBackup[] = $foreign;
                $this->dropForeign($table, $foreign);
            }
        }

        $this->run("ALTER TABLE {$this->identify($table)} CHANGE {$this->identify($initial)} {$column->sqlStatement($this->driver)}");

        //Restoring FKs
        foreach ($foreignBackup as $foreign) {
            $this->createForeign($table, $foreign);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dropIndex(AbstractTable $table, AbstractIndex $index)
    {
        $this->run("DROP INDEX {$this->identify($index)} ON {$this->identify($table)}");

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function alterIndex(AbstractTable $table, AbstractIndex $initial, AbstractIndex $index)
    {
        $this->run("ALTER TABLE {$this->identify($table)} DROP INDEX  {$this->identify($index)}, ADD {$index->sqlStatement($this->driver, false)}");
    }

    /**
     * {@inheritdoc}
     */
    public function dropForeign(AbstractTable $table, AbstractReference $foreign)
    {
        $this->run("ALTER TABLE {$this->identify($table)} DROP FOREIGN KEY {$this->identify($foreign)}");
    }

    /**
     * Get statement needed to create table.
     *
     * @param AbstractTable $table
     *
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
     * @throws MySQLDriverException
     */
    protected function assertValid(AbstractColumn $column)
    {
        if (
            in_array(
                $column->abstractType(),
                ['text', 'tinyText', 'longText', 'blob', 'tinyBlob', 'longBlob']
            )
            && is_string($column->getDefaultValue()) && $column->getDefaultValue() !== ''
        ) {
            throw new MySQLDriverException(
                "Column {$column} of type text/blob can not have non empty default value"
            );
        }
    }
}