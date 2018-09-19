<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database;

use Psr\Log\LoggerInterface;
use Spiral\Database\AbstractHandler as Behaviour;
use Spiral\Database\Driver;
use Spiral\Database\Schema\AbstractTable;
use Spiral\Support\DFSSorter;

/**
 * Saves multiple linked tables at once but treating their cross dependency.
 *
 * Attention, not every DBMS support transactional schema manipulations!
 */
class SynchronizationPool
{
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
        $sorter = new DFSSorter();
        foreach ($this->tables as $table) {
            $sorter->addItem($table->getName(), $table, $table->getDependencies());
        }

        return $sorter->sort();
    }

    /**
     * Synchronize tables.
     *
     * @param LoggerInterface|null $logger
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function run(LoggerInterface $logger = null)
    {
        $hasChanges = false;
        foreach ($this->tables as $table) {
            //todo: test drop
            if ($table->getComparator()->hasChanges() || $table->getStatus() == AbstractTable::STATUS_DECLARED_DROPPED) {
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
            $this->dropForeigns($logger);

            //Drop not-needed indexes
            $this->dropIndexes($logger);

            //Other changes [NEW TABLES WILL BE CREATED HERE!]
            foreach ($this->runChanges($logger) as $table) {
                $table->save(Behaviour::CREATE_FOREIGNS, $logger, true);
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

    /**
     * @param LoggerInterface|null $logger
     */
    protected function dropForeigns(LoggerInterface $logger = null)
    {
        foreach ($this->sortedTables() as $table) {
            if ($table->exists()) {
                $table->save(Behaviour::DROP_FOREIGNS, $logger, false);
            }
        }
    }

    /**
     * @param LoggerInterface|null $logger
     */
    protected function dropIndexes(LoggerInterface $logger = null)
    {
        foreach ($this->sortedTables() as $table) {
            if ($table->exists()) {
                $table->save(Behaviour::DROP_INDEXES, $logger, false);
            }
        }
    }

    /**
     * @param LoggerInterface|null $logger
     *
     * @return AbstractTable[] Created or updated tables.
     */
    protected function runChanges(LoggerInterface $logger = null): array
    {
        $tables = [];
        foreach ($this->sortedTables() as $table) {
            if ($table->getStatus() == AbstractTable::STATUS_DECLARED_DROPPED) {
                $table->save(Behaviour::DO_DROP, $logger);
            } else {
                $tables[] = $table;
                $table->save(
                    Behaviour::DO_ALL ^ Behaviour::DROP_FOREIGNS ^ Behaviour::DROP_INDEXES ^ Behaviour::CREATE_FOREIGNS,
                    $logger
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
}
