<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Schema;

use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\HandlerInterface;

/**
 * Saves multiple linked tables at once but treating their cross dependency.
 * Attention, not every DBMS support transactional schema manipulations!
 */
final class Reflector
{
    public const STATE_NEW = 1;
    public const STATE_PASSED = 2;

    /** @var AbstractTable[] */
    private array $tables = [];

    private array $dependencies = [];

    /** @var DriverInterface[] */
    private array $drivers = [];

    private array $states = [];
    private array $stack = [];

    /**
     * Add table to the collection.
     */
    public function addTable(AbstractTable $table): void
    {
        $this->tables[$table->getFullName()] = $table;
        $this->dependencies[$table->getFullName()] = $table->getDependencies();

        $this->collectDrivers();
    }

    /**
     * @return AbstractTable[]
     */
    public function getTables(): array
    {
        return \array_values($this->tables);
    }

    /**
     * Return sorted stack.
     */
    public function sortedTables(): array
    {
        $items = \array_keys($this->tables);
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
    public function run(): void
    {
        $hasChanges = false;
        foreach ($this->tables as $table) {
            if (
                $table->getStatus() === AbstractTable::STATUS_DECLARED_DROPPED
                || $table->getComparator()->hasChanges()
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
    protected function dropForeignKeys(): void
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
    protected function dropIndexes(): void
    {
        foreach ($this->sortedTables() as $table) {
            if ($table->exists()) {
                $table->save(HandlerInterface::DROP_INDEXES, false);
            }
        }
    }

    /*
     * @return AbstractTable[] Created or updated tables.
     */
    protected function commitChanges(): array
    {
        $updated = [];
        foreach ($this->sortedTables() as $table) {
            if ($table->getStatus() === AbstractTable::STATUS_DECLARED_DROPPED) {
                $table->save(HandlerInterface::DO_DROP);
                continue;
            }

            $updated[] = $table;
            $table->save(
                HandlerInterface::DO_ALL
                ^ HandlerInterface::DROP_FOREIGN_KEYS
                ^ HandlerInterface::DROP_INDEXES
                ^ HandlerInterface::CREATE_FOREIGN_KEYS,
            );
        }

        return $updated;
    }

    /**
     * Begin mass transaction.
     */
    protected function beginTransaction(): void
    {
        foreach ($this->drivers as $driver) {
            if ($driver instanceof Driver) {
                // do not cache statements for this transaction
                $driver->beginTransaction(null, false);
            } else {
                $driver->beginTransaction(null);
            }
        }
    }

    /**
     * Commit mass transaction.
     */
    protected function commitTransaction(): void
    {
        foreach ($this->drivers as $driver) {
            $driver->commitTransaction();
        }
    }

    /**
     * Roll back mass transaction.
     */
    protected function rollbackTransaction(): void
    {
        foreach (\array_reverse($this->drivers) as $driver) {
            $driver->rollbackTransaction();
        }
    }

    /**
     * Collecting all involved drivers.
     */
    private function collectDrivers(): void
    {
        foreach ($this->tables as $table) {
            if (!\in_array($table->getDriver(), $this->drivers, true)) {
                $this->drivers[] = $table->getDriver();
            }
        }
    }

    /**
     * @psalm-param non-empty-string $key
     */
    private function sort(string $key, array $dependencies): void
    {
        if (isset($this->states[$key])) {
            return;
        }

        $this->states[$key] = self::STATE_NEW;
        foreach ($dependencies as $dependency) {
            if (isset($this->dependencies[$dependency])) {
                $this->sort($dependency, $this->dependencies[$dependency]);
            }
        }

        $this->stack[] = $this->tables[$key];
        $this->states[$key] = self::STATE_PASSED;
    }
}
