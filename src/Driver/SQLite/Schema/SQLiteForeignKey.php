<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLite\Schema;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Schema\AbstractForeignKey;

class SQLiteForeignKey extends AbstractForeignKey
{
    public static function createInstance(string $table, string $tablePrefix, array $schema): self
    {
        $reference = new self($table, $tablePrefix, (string) $schema['id']);

        $reference->columns = $schema['from'];
        $reference->foreignTable = $schema['table'];
        $reference->foreignKeys = $schema['to'];

        //In SQLLite we have to work with pre-defined reference names
        $reference->name = $reference->getName();

        $reference->deleteRule = $schema['on_delete'];
        $reference->updateRule = $schema['on_update'];

        return $reference;
    }

    /**
     * In SQLite we have no predictable name.
     *
     */
    public function getName(): string
    {
        return $this->tablePrefix . $this->table . '_' . \implode('_', $this->columns) . '_fk';
    }

    public function sqlStatement(DriverInterface $driver): string
    {
        $statement = [];

        $statement[] = 'FOREIGN KEY';
        $statement[] = '(' . $this->packColumns($driver, $this->columns) . ')';

        $statement[] = 'REFERENCES ' . $driver->identifier($this->foreignTable);
        $statement[] = '(' . $this->packColumns($driver, $this->foreignKeys) . ')';

        $statement[] = "ON DELETE {$this->deleteRule}";
        $statement[] = "ON UPDATE {$this->updateRule}";

        return \implode(' ', $statement);
    }

    /**
     * Name insensitive compare.
     *
     */
    public function compare(AbstractForeignKey $initial): bool
    {
        return $this->getColumns() === $initial->getColumns()
            && $this->getForeignTable() === $initial->getForeignTable()
            && $this->getForeignKeys() === $initial->getForeignKeys()
            && $this->getUpdateRule() === $initial->getUpdateRule()
            && $this->getDeleteRule() === $initial->getDeleteRule();
    }
}
