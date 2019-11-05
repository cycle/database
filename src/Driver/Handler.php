<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver;

use Spiral\Database\Exception\DBALException;
use Spiral\Database\Exception\DriverException;
use Spiral\Database\Exception\HandlerException;
use Spiral\Database\Exception\StatementException;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractForeignKey;
use Spiral\Database\Schema\AbstractIndex;
use Spiral\Database\Schema\AbstractTable;
use Spiral\Database\Schema\Comparator;
use Spiral\Database\Schema\ElementInterface;

/**
 * Handler class implements set of DBMS specific operations for schema manipulations. Can be used
 * on separate basis (for example in migrations).
 */
abstract class Handler implements HandlerInterface
{
    /** @var DriverInterface */
    protected $driver;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Associated driver.
     *
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * @inheritdoc
     */
    public function createTable(AbstractTable $table): void
    {
        $this->run($this->createStatement($table));

        //Not all databases support adding index while table creation, so we can do it after
        foreach ($table->getIndexes() as $index) {
            $this->createIndex($table, $index);
        }
    }

    /**
     * @inheritdoc
     */
    public function dropTable(AbstractTable $table): void
    {
        $this->run("DROP TABLE {$this->identify($table->getInitialName())}");
    }

    /**
     * @inheritdoc
     */
    public function syncTable(AbstractTable $table, int $operation = self::DO_ALL): void
    {
        $comparator = $table->getComparator();

        if ($comparator->isPrimaryChanged()) {
            throw new DBALException('Unable to change primary keys for existed table');
        }

        if ($comparator->isRenamed() && $operation & self::DO_RENAME) {
            $this->renameTable($table->getInitialName(), $table->getName());
        }

        /*
         * This is schema synchronization code, if you are reading it you are either experiencing
         * VERY weird bug, or you are very curious. Please contact me in a any scenario :)
         */
        $this->executeChanges($table, $operation, $comparator);
    }

    /**
     * @inheritdoc
     */
    public function renameTable(string $table, string $name): void
    {
        $this->run("ALTER TABLE {$this->identify($table)} RENAME TO {$this->identify($name)}");
    }

    /**
     * @inheritdoc
     */
    public function createColumn(AbstractTable $table, AbstractColumn $column): void
    {
        $this->run(
            "ALTER TABLE {$this->identify($table)} ADD COLUMN {$column->sqlStatement($this->driver)}"
        );
    }

    /**
     * @inheritdoc
     */
    public function dropColumn(AbstractTable $table, AbstractColumn $column): void
    {
        foreach ($column->getConstraints() as $constraint) {
            //We have to erase all associated constraints
            $this->dropConstrain($table, $constraint);
        }

        $this->run("ALTER TABLE {$this->identify($table)} DROP COLUMN {$this->identify($column)}");
    }

    /**
     * @inheritdoc
     */
    public function createIndex(AbstractTable $table, AbstractIndex $index): void
    {
        $this->run("CREATE {$index->sqlStatement($this->driver)}");
    }

    /**
     * @inheritdoc
     */
    public function dropIndex(AbstractTable $table, AbstractIndex $index): void
    {
        $this->run("DROP INDEX {$this->identify($index)}");
    }

    /**
     * @inheritdoc
     */
    public function alterIndex(AbstractTable $table, AbstractIndex $initial, AbstractIndex $index): void
    {
        $this->dropIndex($table, $initial);
        $this->createIndex($table, $index);
    }

    /**
     * @inheritdoc
     */
    public function createForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void
    {
        $this->run("ALTER TABLE {$this->identify($table)} ADD {$foreignKey->sqlStatement($this->driver)}");
    }

    /**
     * @inheritdoc
     */
    public function dropForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void
    {
        $this->dropConstrain($table, $foreignKey->getName());
    }

    /**
     * @inheritdoc
     */
    public function alterForeignKey(
        AbstractTable $table,
        AbstractForeignKey $initial,
        AbstractForeignKey $foreignKey
    ): void {
        $this->dropForeignKey($table, $initial);
        $this->createForeignKey($table, $foreignKey);
    }

    /**
     * @inheritdoc
     */
    public function dropConstrain(AbstractTable $table, $constraint): void
    {
        $this->run("ALTER TABLE {$this->identify($table)} DROP CONSTRAINT {$this->identify($constraint)}");
    }

    /**
     * @inheritdoc
     */
    protected function createStatement(AbstractTable $table)
    {
        $statement = ["CREATE TABLE {$this->identify($table)} ("];
        $innerStatement = [];

        //Columns
        foreach ($table->getColumns() as $column) {
            $this->assertValid($column);
            $innerStatement[] = $column->sqlStatement($this->driver);
        }

        //Primary key
        if (!empty($table->getPrimaryKeys())) {
            $primaryKeys = array_map([$this, 'identify'], $table->getPrimaryKeys());

            $innerStatement[] = 'PRIMARY KEY (' . join(', ', $primaryKeys) . ')';
        }

        //Constraints and foreign keys
        foreach ($table->getForeignKeys() as $reference) {
            $innerStatement[] = $reference->sqlStatement($this->driver);
        }

        $statement[] = '    ' . join(",\n    ", $innerStatement);
        $statement[] = ')';

        return join("\n", $statement);
    }

    /**
     * @param AbstractTable $table
     * @param int           $operation
     * @param Comparator    $comparator
     */
    protected function executeChanges(AbstractTable $table, int $operation, Comparator $comparator): void
    {
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
     * @param string $statement
     * @param array  $parameters
     * @return int
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
     * @param ElementInterface|AbstractTable|string $element
     * @return string
     */
    protected function identify($element): string
    {
        if (is_string($element)) {
            return $this->driver->identifier($element);
        }

        if (!$element instanceof ElementInterface && !$element instanceof AbstractTable) {
            throw new \InvalidArgumentException('Invalid argument type');
        }

        return $this->driver->identifier($element->getName());
    }

    /**
     * @param AbstractTable $table
     * @param Comparator    $comparator
     */
    protected function alterForeignKeys(AbstractTable $table, Comparator $comparator): void
    {
        foreach ($comparator->alteredForeignKeys() as $pair) {
            /**
             * @var AbstractForeignKey $initial
             * @var AbstractForeignKey $current
             */
            list($current, $initial) = $pair;

            $this->alterForeignKey($table, $initial, $current);
        }
    }

    /**
     * @param AbstractTable $table
     * @param Comparator    $comparator
     */
    protected function createForeignKeys(AbstractTable $table, Comparator $comparator): void
    {
        foreach ($comparator->addedForeignKeys() as $foreign) {
            $this->createForeignKey($table, $foreign);
        }
    }

    /**
     * @param AbstractTable $table
     * @param Comparator    $comparator
     */
    protected function alterIndexes(AbstractTable $table, Comparator $comparator): void
    {
        foreach ($comparator->alteredIndexes() as $pair) {
            /**
             * @var AbstractIndex $initial
             * @var AbstractIndex $current
             */
            list($current, $initial) = $pair;

            $this->alterIndex($table, $initial, $current);
        }
    }

    /**
     * @param AbstractTable $table
     * @param Comparator    $comparator
     */
    protected function createIndexes(AbstractTable $table, Comparator $comparator): void
    {
        foreach ($comparator->addedIndexes() as $index) {
            $this->createIndex($table, $index);
        }
    }

    /**
     * @param AbstractTable $table
     * @param Comparator    $comparator
     */
    protected function alterColumns(AbstractTable $table, Comparator $comparator): void
    {
        foreach ($comparator->alteredColumns() as $pair) {
            /**
             * @var AbstractColumn $initial
             * @var AbstractColumn $current
             */
            list($current, $initial) = $pair;

            $this->assertValid($current);
            $this->alterColumn($table, $initial, $current);
        }
    }

    /**
     * @param AbstractTable $table
     * @param Comparator    $comparator
     */
    protected function createColumns(AbstractTable $table, Comparator $comparator): void
    {
        foreach ($comparator->addedColumns() as $column) {
            $this->assertValid($column);
            $this->createColumn($table, $column);
        }
    }

    /**
     * @param AbstractTable $table
     * @param Comparator    $comparator
     */
    protected function dropColumns(AbstractTable $table, Comparator $comparator): void
    {
        foreach ($comparator->droppedColumns() as $column) {
            $this->dropColumn($table, $column);
        }
    }

    /**
     * @param AbstractTable $table
     * @param Comparator    $comparator
     */
    protected function dropIndexes(AbstractTable $table, Comparator $comparator): void
    {
        foreach ($comparator->droppedIndexes() as $index) {
            $this->dropIndex($table, $index);
        }
    }

    /**
     * @param AbstractTable $table
     * @param Comparator    $comparator
     */
    protected function dropForeignKeys(AbstractTable $table, Comparator $comparator): void
    {
        foreach ($comparator->droppedForeignKeys() as $foreign) {
            $this->dropForeignKey($table, $foreign);
        }
    }

    /**
     * Applied to every column in order to make sure that driver support it.
     *
     * @param AbstractColumn $column
     *
     * @throws DriverException
     */
    protected function assertValid(AbstractColumn $column): void
    {
        //All valid by default
    }

    /**
     * @param AbstractTable $table
     * @param int           $operation
     * @param Comparator    $comparator
     */
    protected function dropConstrains(AbstractTable $table, int $operation, Comparator $comparator): void
    {
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

    /**
     * @param AbstractTable $table
     * @param int           $operation
     * @param Comparator    $comparator
     */
    protected function setConstrains(AbstractTable $table, int $operation, Comparator $comparator): void
    {
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
