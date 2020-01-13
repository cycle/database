<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\SQLite\Schema;

use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Schema\AbstractForeignKey;

class SQLiteForeignKey extends AbstractForeignKey
{
    /**
     * In SQLite we have no predictable name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->tablePrefix . $this->table . '_' . implode('_', $this->columns) . '_fk';
    }

    /**
     * {@inheritdoc}
     */
    public function sqlStatement(DriverInterface $driver): string
    {
        $statement = [];

        $statement[] = 'FOREIGN KEY';
        $statement[] = '(' . $this->packColumns($driver, $this->columns) . ')';

        $statement[] = 'REFERENCES ' . $driver->identifier($this->foreignTable);
        $statement[] = '(' . $this->packColumns($driver, $this->foreignKeys) . ')';

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
        return $this->getColumns() === $initial->getColumns()
            && $this->getForeignTable() === $initial->getForeignTable()
            && $this->getForeignKeys() === $initial->getForeignKeys()
            && $this->getUpdateRule() === $initial->getUpdateRule()
            && $this->getDeleteRule() === $initial->getDeleteRule();
    }

    /**
     * @param string $table
     * @param string $tablePrefix
     * @param array  $schema
     * @return SQLiteForeignKey
     */
    public static function createInstance(string $table, string $tablePrefix, array $schema): self
    {
        $reference = new self($table, $tablePrefix, $schema['id']);

        $reference->columns = $schema['from'];
        $reference->foreignTable = $schema['table'];
        $reference->foreignKeys = $schema['to'];

        //In SQLLite we have to work with pre-defined reference names
        $reference->name = $reference->getName();

        $reference->deleteRule = $schema['on_delete'];
        $reference->updateRule = $schema['on_update'];

        return $reference;
    }
}
