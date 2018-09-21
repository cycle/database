<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Driver\SQLite\Schema;

use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Schema\AbstractForeignKey;

class SQLiteForeign extends AbstractForeignKey
{
    /**
     * In SQLite we have no predictable name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->tablePrefix . $this->table . '_' . $this->column . '_fk';
    }

    /**
     * {@inheritdoc}
     */
    public function sqlStatement(DriverInterface $driver): string
    {
        $statement = [];

        $statement[] = 'FOREIGN KEY';
        $statement[] = '(' . $driver->identifier($this->column) . ')';

        $statement[] = 'REFERENCES ' . $driver->identifier($this->foreignTable);
        $statement[] = '(' . $driver->identifier($this->foreignKey) . ')';

        $statement[] = "ON DELETE {$this->deleteRule}";
        $statement[] = "ON UPDATE {$this->updateRule}";

        return implode(' ', $statement);
    }

    /**
     * Name insensitive compare.
     *
     * @param AbstractForeignKey $initial
     * @return bool
     */
    public function compare(AbstractForeignKey $initial): bool
    {
        return $this->getColumn() == $initial->getColumn()
            && $this->getForeignTable() == $initial->getForeignTable()
            && $this->getForeignKey() == $initial->getForeignKey()
            && $this->getUpdateRule() == $initial->getUpdateRule()
            && $this->getDeleteRule() == $initial->getDeleteRule();
    }

    /**
     * @param string $table
     * @param string $tablePrefix
     * @param array  $schema
     * @return SQLiteForeign
     */
    public static function createInstance(string $table, string $tablePrefix, array $schema): self
    {
        $reference = new self($table, $tablePrefix, $schema['id']);

        $reference->column = $schema['from'];

        $reference->foreignTable = $schema['table'];
        $reference->foreignKey = $schema['to'];

        //In SQLLite we have to work with pre-defined reference names
        $reference->name = $reference->getName();

        $reference->deleteRule = $schema['on_delete'];
        $reference->updateRule = $schema['on_update'];

        return $reference;
    }
}
