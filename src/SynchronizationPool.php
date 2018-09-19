<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database;

use Spiral\Database\Driver\AbstractHandler as Behaviour;
use Spiral\Database\Driver\Driver;
use Spiral\Database\Schema\AbstractTable;

/**
 * Saves multiple linked tables at once but treating their cross dependency.
 *
 * Attention, not every DBMS support transactional schema manipulations!
 */
class SynchronizationPool
{
    const STATE_NEW    = 1;
    const STATE_PASSED = 2;

    /** @var array string[] */
    private $keys = [];

    /** @var array */
    private $states = [];

    /** @var array mixed[] */
    private $stack = [];

    /** @var array mixed[] */
    private $objects = [];

    /** @var array mixed[] */
    private $dependencies = [];

    /**
     * @var AbstractTable[]
     */
    private $tables = [];

    /**
     * @var Driver[]
     */
    private $drivers = [];

    /**
     * @param AbstractTable[] $tables
     */
    public function __construct(array $tables)
    {
        $this->tables = $tables;

        $this->collectDrivers();
    }

    /**
     * @return AbstractTable[]
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * List of tables sorted in order of cross dependency.
     *
     * @return AbstractTable[]
     */
    public function sortedTables(): array
    {
        /*
         * Tables has to be sorted using topological graph to execute operations in a valid order.
         */
        foreach ($this->tables as $table) {
            $this->addItem($table->getName(), $table, $table->getDependencies());
        }

        return $this->sort();
    }

    /**
     * Synchronize tables.
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function run()
    {
        $hasChanges = false;
        foreach ($this->tables as $table) {
            //todo: test drop
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
            $this->dropForeigns();

            //Drop not-needed indexes
            $this->dropIndexes();

            //Other changes [NEW TABLES WILL BE CREATED HERE!]
            foreach ($this->runChanges() as $table) {
                $table->save(Behaviour::CREATE_FOREIGNS, true);
            }

        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }

        $this->commitTransaction();
    }

    /**
     * Begin mass transaction.
     */
    protected function beginTransaction()
    {
        foreach ($this->drivers as $driver) {
            $driver->beginTransaction();
        }
    }

    /**
     * Commit mass transaction.
     */
    protected function commitTransaction()
    {
        foreach ($this->drivers as $driver) {
            /**
             * @var Driver $driver
             */
            $driver->commitTransaction();
        }
    }

    /**
     * Roll back mass transaction.
     */
    protected function rollbackTransaction()
    {
        foreach (array_reverse($this->drivers) as $driver) {
            /**
             * @var Driver $driver
             */
            $driver->rollbackTransaction();
        }
    }


    protected function dropForeigns()
    {
        foreach ($this->sortedTables() as $table) {
            if ($table->exists()) {
                $table->save(Behaviour::DROP_FOREIGNS, false);
            }
        }
    }

    protected function dropIndexes()
    {
        foreach ($this->sortedTables() as $table) {
            if ($table->exists()) {
                $table->save(Behaviour::DROP_INDEXES, false);
            }
        }
    }

    /***
     * @return AbstractTable[] Created or updated tables.
     */
    protected function runChanges(): array
    {
        $tables = [];
        foreach ($this->sortedTables() as $table) {
            if ($table->getStatus() == AbstractTable::STATUS_DECLARED_DROPPED) {
                $table->save(Behaviour::DO_DROP);
            } else {
                $tables[] = $table;
                $table->save(
                    Behaviour::DO_ALL
                    ^ Behaviour::DROP_FOREIGNS
                    ^ Behaviour::DROP_INDEXES
                    ^ Behaviour::CREATE_FOREIGNS
                );
            }
        }

        return $tables;
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
     * @param string $key          Item key, has to be used as reference in dependencies.
     * @param mixed  $item
     * @param array  $dependencies Must include keys object depends on.
     * @return self
     */
    private function addItem(string $key, $item, array $dependencies)
    {
        $this->keys[] = $key;
        $this->objects[$key] = $item;
        $this->dependencies[$key] = $dependencies;

        return $this;
    }

    /**
     * Return sorted stack.
     *
     * @return array
     */
    private function sort(): array
    {
        $items = array_values($this->keys);
        $this->states = $this->stack = [];

        foreach ($items as $item) {
            $this->dfs($item, $this->dependencies[$item]);
        }

        return $this->stack;
    }

    /**
     * @param string $key
     * @param array  $dependencies
     */
    private function dfs(string $key, array $dependencies)
    {
        if (isset($this->states[$key])) {
            return;
        }

        $this->states[$key] = self::STATE_NEW;
        foreach ($dependencies as $dependency) {
            $this->dfs($dependency, $this->dependencies[$dependency]);
        }

        $this->stack[] = $this->objects[$key];
        $this->states[$key] = self::STATE_PASSED;

        return;
    }
}
