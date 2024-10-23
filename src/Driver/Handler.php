<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use Cycle\Database\Exception\DBALException;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Exception\HandlerException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Schema\ComparatorInterface;
use Cycle\Database\Schema\ElementInterface;

abstract class Handler implements HandlerInterface
{
    protected ?DriverInterface $driver = null;

    public function withDriver(DriverInterface $driver): HandlerInterface
    {
        $handler = clone $this;
        $handler->driver = $driver;

        return $handler;
    }

    /**
     * Associated driver.
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    public function createTable(AbstractTable $table): void
    {
        $this->run($this->createStatement($table));

        //Not all databases support adding index while table creation, so we can do it after
        foreach ($table->getIndexes() as $index) {
            $this->createIndex($table, $index);
        }
    }

    public function dropTable(AbstractTable $table): void
    {
        $this->run(
            "DROP TABLE {$this->identify($table->getInitialName())}",
        );
    }

    public function syncTable(AbstractTable $table, int $operation = self::DO_ALL): void
    {
        $comparator = $table->getComparator();

        $comparator->isPrimaryChanged() and throw new DBALException('Unable to change primary keys for existed table');

        if ($operation & self::DO_RENAME && $comparator->isRenamed()) {
            $this->renameTable($table->getInitialName(), $table->getFullName());
        }

        /*
         * This is schema synchronization code, if you are reading it you are either experiencing
         * VERY weird bug, or you are very curious. Please contact me in a any scenario :)
         */
        $this->executeChanges($table, $operation, $comparator);
    }

    /**
     * @psalm-param non-empty-string $table
     * @psalm-param non-empty-string $name
     */
    public function renameTable(string $table, string $name): void
    {
        $this->run(
            "ALTER TABLE {$this->identify($table)} RENAME TO {$this->identify($name)}",
        );
    }

    public function createColumn(AbstractTable $table, AbstractColumn $column): void
    {
        $this->run(
            "ALTER TABLE {$this->identify($table)} ADD COLUMN {$column->sqlStatement($this->driver)}",
        );
    }

    public function dropColumn(AbstractTable $table, AbstractColumn $column): void
    {
        foreach ($column->getConstraints() as $constraint) {
            //We have to erase all associated constraints
            $this->dropConstrain($table, $constraint);
        }

        $this->run(
            "ALTER TABLE {$this->identify($table)} DROP COLUMN {$this->identify($column)}",
        );
    }

    public function createIndex(AbstractTable $table, AbstractIndex $index): void
    {
        $this->run("CREATE {$index->sqlStatement($this->driver)}");
    }

    public function dropIndex(AbstractTable $table, AbstractIndex $index): void
    {
        $this->run("DROP INDEX {$this->identify($index)}");
    }

    public function alterIndex(
        AbstractTable $table,
        AbstractIndex $initial,
        AbstractIndex $index,
    ): void {
        $this->dropIndex($table, $initial);
        $this->createIndex($table, $index);
    }

    public function createForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void
    {
        $this->run(
            "ALTER TABLE {$this->identify($table)} ADD {$foreignKey->sqlStatement($this->driver)}",
        );
    }

    public function dropForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void
    {
        $this->dropConstrain($table, $foreignKey->getName());
    }

    public function alterForeignKey(
        AbstractTable $table,
        AbstractForeignKey $initial,
        AbstractForeignKey $foreignKey,
    ): void {
        $this->dropForeignKey($table, $initial);
        $this->createForeignKey($table, $foreignKey);
    }

    public function dropConstrain(AbstractTable $table, string $constraint): void
    {
        $this->run(
            "ALTER TABLE {$this->identify($table)} DROP CONSTRAINT {$this->identify($constraint)}",
        );
    }

    /**
     * @psalm-return non-empty-string
     */
    protected function createStatement(AbstractTable $table): string
    {
        $statement = ["CREATE TABLE {$this->identify($table)} ("];
        $innerStatement = [];

        //Columns
        foreach ($table->getColumns() as $column) {
            $this->assertValid($column);
            $innerStatement[] = $column->sqlStatement($this->driver);
        }

        //Primary key
        if ($table->getPrimaryKeys() !== []) {
            $primaryKeys = \array_map([$this, 'identify'], $table->getPrimaryKeys());

            $innerStatement[] = 'PRIMARY KEY (' . \implode(', ', $primaryKeys) . ')';
        }

        //Constraints and foreign keys
        foreach ($table->getForeignKeys() as $reference) {
            $innerStatement[] = $reference->sqlStatement($this->driver);
        }

        $statement[] = '    ' . \implode(",\n    ", $innerStatement);
        $statement[] = ')';

        return \implode("\n", $statement);
    }

    protected function executeChanges(
        AbstractTable $table,
        int $operation,
        ComparatorInterface $comparator,
    ): void {
        //Remove all non needed table constraints
        $this->dropConstrains($table, $operation, $comparator);

        if ($operation & self::CREATE_COLUMNS) {
            //After drops and before creations we can add new columns
            $this->createColumns($table, $comparator);
        }

        if ($operation & self::ALTER_COLUMNS) {
            //We can alter columns now
            $this->alterColumns($table, $comparator);
        }

        //Add new constrains and modify existed one
        $this->setConstrains($table, $operation, $comparator);
    }

    /**
     * Execute statement.
     *
     * @psalm-param non-empty-string $statement
     *
     * @throws HandlerException
     */
    protected function run(string $statement, array $parameters = []): int
    {
        try {
            return $this->driver->execute($statement, $parameters);
        } catch (StatementException $e) {
            throw new HandlerException($e);
        }
    }

    /**
     * Create element identifier.
     *
     * @psalm-return non-empty-string
     */
    protected function identify(AbstractTable|ElementInterface|string $element): string
    {
        if (\is_string($element)) {
            return $this->driver->identifier($element);
        }

        if ($element instanceof AbstractTable) {
            return $this->driver->identifier($element->getFullName());
        }

        if ($element instanceof ElementInterface) {
            return $this->driver->identifier($element->getName());
        }

        throw new \InvalidArgumentException('Invalid argument type');
    }

    protected function alterForeignKeys(AbstractTable $table, ComparatorInterface $comparator): void
    {
        foreach ($comparator->alteredForeignKeys() as $pair) {
            /**
             * @var AbstractForeignKey $initial
             * @var AbstractForeignKey $current
             */
            [$current, $initial] = $pair;

            $this->alterForeignKey($table, $initial, $current);
        }
    }

    protected function createForeignKeys(AbstractTable $table, ComparatorInterface $comparator): void
    {
        foreach ($comparator->addedForeignKeys() as $foreign) {
            $this->createForeignKey($table, $foreign);
        }
    }

    protected function alterIndexes(AbstractTable $table, ComparatorInterface $comparator): void
    {
        foreach ($comparator->alteredIndexes() as $pair) {
            /**
             * @var AbstractIndex $initial
             * @var AbstractIndex $current
             */
            [$current, $initial] = $pair;

            $this->alterIndex($table, $initial, $current);
        }
    }

    protected function createIndexes(AbstractTable $table, ComparatorInterface $comparator): void
    {
        foreach ($comparator->addedIndexes() as $index) {
            $this->createIndex($table, $index);
        }
    }

    protected function alterColumns(AbstractTable $table, ComparatorInterface $comparator): void
    {
        foreach ($comparator->alteredColumns() as $pair) {
            /**
             * @var AbstractColumn $initial
             * @var AbstractColumn $current
             */
            [$current, $initial] = $pair;

            $this->assertValid($current);
            $this->alterColumn($table, $initial, $current);
        }
    }

    protected function createColumns(AbstractTable $table, ComparatorInterface $comparator): void
    {
        foreach ($comparator->addedColumns() as $column) {
            $this->assertValid($column);
            $this->createColumn($table, $column);
        }
    }

    protected function dropColumns(AbstractTable $table, ComparatorInterface $comparator): void
    {
        foreach ($comparator->droppedColumns() as $column) {
            $this->dropColumn($table, $column);
        }
    }

    protected function dropIndexes(AbstractTable $table, ComparatorInterface $comparator): void
    {
        foreach ($comparator->droppedIndexes() as $index) {
            $this->dropIndex($table, $index);
        }
    }

    protected function dropForeignKeys(AbstractTable $table, ComparatorInterface $comparator): void
    {
        foreach ($comparator->droppedForeignKeys() as $foreign) {
            $this->dropForeignKey($table, $foreign);
        }
    }

    /**
     * Applied to every column in order to make sure that driver support it.
     *
     * @throws DriverException
     */
    protected function assertValid(AbstractColumn $column): void
    {
        //All valid by default
    }

    protected function dropConstrains(
        AbstractTable $table,
        int $operation,
        ComparatorInterface $comparator,
    ): void {
        if ($operation & self::DROP_FOREIGN_KEYS) {
            $this->dropForeignKeys($table, $comparator);
        }

        if ($operation & self::DROP_INDEXES) {
            $this->dropIndexes($table, $comparator);
        }

        if ($operation & self::DROP_COLUMNS) {
            $this->dropColumns($table, $comparator);
        }
    }

    protected function setConstrains(
        AbstractTable $table,
        int $operation,
        ComparatorInterface $comparator,
    ): void {
        if ($operation & self::CREATE_INDEXES) {
            $this->createIndexes($table, $comparator);
        }

        if ($operation & self::ALTER_INDEXES) {
            $this->alterIndexes($table, $comparator);
        }

        if ($operation & self::CREATE_FOREIGN_KEYS) {
            $this->createForeignKeys($table, $comparator);
        }

        if ($operation & self::ALTER_FOREIGN_KEYS) {
            $this->alterForeignKeys($table, $comparator);
        }
    }
}
