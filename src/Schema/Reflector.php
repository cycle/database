<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Schema;

use Spiral\Database\Driver\AbstractDriver;
use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Driver\HandlerInterface;

/**
 * Saves multiple linked tables at once but treating their cross dependency.
 * Attention, not every DBMS support transactional schema manipulations!
 */
final class Reflector
{
    const STATE_NEW    = 1;
    const STATE_PASSED = 2;

    /** @var AbstractTable[] */
    private $tables = [];

    /** @var array mixed[] */
    private $dependencies = [];

    /** @var DriverInterface[] */
    private $drivers = [];

    /** @var array */
    private $states = [];

    /** @var array mixed[] */
    private $stack = [];

    /**
     * Add table to the collection.
     *
     * @param AbstractTable $table
     */
    public function addTable(AbstractTable $table)
    {
        $this->tables[$table->getName()] = $table;
        $this->dependencies[$table->getName()] = $table->getDependencies();

        $this->collectDrivers();
    }

    /**
     * @return AbstractTable[]
     */
    public function getTables()
    {
        return array_values($this->tables);
    }

    /**
     * Return sorted stack.
     *
     * @return array
     */
    public function sortedTables(): array
    {
        $items = array_keys($this->tables);
        $this->states = $this->stack = [];

        foreach ($items as $item) {
            $this->sort($item, $this->dependencies[$item]);
        }

        return $this->stack;
    }

    /**
     * Synchronize tables.
     *
     * @throws \Throwable
     */
    public function run()
    {
        $hasChanges = false;
        foreach ($this->tables as $table) {
            if (
                $table->getComparator()->hasChanges()
                || $table->getStatus() == AbstractTable::STATUS_DECLARED_DROPPED
            ) {
                $hasChanges = true;
                break;
            }
        }

        if (!$hasChanges) {
            //Nothing to do
            return;
        }

        $this->beginTransaction();

        try {
            //Drop not-needed foreign keys and alter everything else
            $this->dropForeignKeys();

            //Drop not-needed indexes
            $this->dropIndexes();

            //Other changes [NEW TABLES WILL BE CREATED HERE!]
            foreach ($this->commitChanges() as $table) {
                $table->save(HandlerInterface::CREATE_FOREIGN_KEYS, true);
            }
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }

        $this->commitTransaction();
    }

    /**
     * Drop all removed table references.
     */
    protected function dropForeignKeys()
    {
        foreach ($this->sortedTables() as $table) {
            if ($table->exists()) {
                $table->save(HandlerInterface::DROP_FOREIGN_KEYS, false);
            }
        }
    }

    /**
     * Drop all removed table indexes.
     */
    protected function dropIndexes()
    {
        foreach ($this->sortedTables() as $table) {
            if ($table->exists()) {
                $table->save(HandlerInterface::DROP_INDEXES, false);
            }
        }
    }

    /***
     * @return AbstractTable[] Created or updated tables.
     */
    protected function commitChanges(): array
    {
        $updated = [];
        foreach ($this->sortedTables() as $table) {
            if ($table->getStatus() == AbstractTable::STATUS_DECLARED_DROPPED) {
                $table->save(HandlerInterface::DO_DROP);
                continue;
            }

            $updated[] = $table;
            $table->save(
                HandlerInterface::DO_ALL
                ^ HandlerInterface::DROP_FOREIGN_KEYS
                ^ HandlerInterface::DROP_INDEXES
                ^ HandlerInterface::CREATE_FOREIGN_KEYS
            );
        }

        return $updated;
    }

    /**
     * Collecting all involved drivers.
     */
    private function collectDrivers()
    {
        foreach ($this->tables as $table) {
            if (!in_array($table->getDriver(), $this->drivers, true)) {
                $this->drivers[] = $table->getDriver();
            }
        }
    }

    /**
     * Begin mass transaction.
     */
    protected function beginTransaction()
    {
        foreach ($this->drivers as $driver) {
            /** @var DriverInterface $driver */
            $driver->beginTransaction();
        }
    }

    /**
     * Commit mass transaction.
     */
    protected function commitTransaction()
    {
        foreach ($this->drivers as $driver) {
            /** @var DriverInterface $driver */
            $driver->commitTransaction();
        }
    }

    /**
     * Roll back mass transaction.
     */
    protected function rollbackTransaction()
    {
        foreach (array_reverse($this->drivers) as $driver) {
            /** @var DriverInterface $driver */
            $driver->rollbackTransaction();
        }
    }

    /**
     * @param string $key
     * @param array  $dependencies
     */
    private function sort(string $key, array $dependencies)
    {
        if (isset($this->states[$key])) {
            return;
        }

        $this->states[$key] = self::STATE_NEW;
        foreach ($dependencies as $dependency) {
            $this->sort($dependency, $this->dependencies[$dependency]);
        }

        $this->stack[] = $this->tables[$key];
        $this->states[$key] = self::STATE_PASSED;

        return;
    }
}
