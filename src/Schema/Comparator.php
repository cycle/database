<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Schema;

/**
 * Compares two table states.
 */
final class Comparator implements ComparatorInterface
{
    /** @var State */
    private $initial;

    /** @var State */
    private $current;

    /**
     * @param State $initial
     * @param State $current
     */
    public function __construct(State $initial, State $current)
    {
        $this->initial = $initial;
        $this->current = $current;
    }

    /**
     * @return bool
     */
    public function hasChanges(): bool
    {
        if ($this->isRenamed()) {
            return true;
        }

        if ($this->isPrimaryChanged()) {
            return true;
        }

        $difference = [
            count($this->addedColumns()),
            count($this->droppedColumns()),
            count($this->alteredColumns()),
            count($this->addedIndexes()),
            count($this->droppedIndexes()),
            count($this->alteredIndexes()),
            count($this->addedForeignKeys()),
            count($this->droppedForeignKeys()),
            count($this->alteredForeignKeys()),
        ];

        return array_sum($difference) !== 0;
    }

    /**
     * @return bool
     */
    public function isRenamed(): bool
    {
        return $this->current->getName() !== $this->initial->getName();
    }

    /**
     * @return bool
     */
    public function isPrimaryChanged(): bool
    {
        return $this->current->getPrimaryKeys() !== $this->initial->getPrimaryKeys();
    }

    /**
     * @return AbstractColumn[]
     */
    public function addedColumns(): array
    {
        $difference = [];

        $initialColumns = $this->initial->getColumns();
        foreach ($this->current->getColumns() as $name => $column) {
            if (!isset($initialColumns[$name])) {
                $difference[] = $column;
            }
        }

        return $difference;
    }

    /**
     * @return AbstractColumn[]
     */
    public function droppedColumns(): array
    {
        $difference = [];

        $currentColumns = $this->current->getColumns();
        foreach ($this->initial->getColumns() as $name => $column) {
            if (!isset($currentColumns[$name])) {
                $difference[] = $column;
            }
        }

        return $difference;
    }

    /**
     * Returns array where each value contain current and initial element state.
     *
     * @return array
     */
    public function alteredColumns(): array
    {
        $difference = [];

        $initialColumns = $this->initial->getColumns();
        foreach ($this->current->getColumns() as $name => $column) {
            if (!isset($initialColumns[$name])) {
                //Added into schema
                continue;
            }

            if (!$column->compare($initialColumns[$name])) {
                $difference[] = [$column, $initialColumns[$name]];
            }
        }

        return $difference;
    }

    /**
     * @return AbstractIndex[]
     */
    public function addedIndexes(): array
    {
        $difference = [];
        foreach ($this->current->getIndexes() as $name => $index) {
            if (!$this->initial->hasIndex($index->getColumns())) {
                $difference[] = $index;
            }
        }

        return $difference;
    }

    /**
     * @return AbstractIndex[]
     */
    public function droppedIndexes(): array
    {
        $difference = [];
        foreach ($this->initial->getIndexes() as $name => $index) {
            if (!$this->current->hasIndex($index->getColumns())) {
                $difference[] = $index;
            }
        }

        return $difference;
    }

    /**
     * Returns array where each value contain current and initial element state.
     *
     * @return array
     */
    public function alteredIndexes(): array
    {
        $difference = [];

        foreach ($this->current->getIndexes() as $name => $index) {
            if (!$this->initial->hasIndex($index->getColumns())) {
                //Added into schema
                continue;
            }

            $initial = $this->initial->findIndex($index->getColumns());
            if (!$index->compare($initial)) {
                $difference[] = [$index, $initial];
            }
        }

        return $difference;
    }

    /**
     * @return AbstractForeignKey[]
     */
    public function addedForeignKeys(): array
    {
        $difference = [];
        foreach ($this->current->getForeignKeys() as $name => $foreignKey) {
            if (!$this->initial->hasForeignKey($foreignKey->getColumns())) {
                $difference[] = $foreignKey;
            }
        }

        return $difference;
    }

    /**
     * @return AbstractForeignKey[]
     */
    public function droppedForeignKeys(): array
    {
        $difference = [];
        foreach ($this->initial->getForeignKeys() as $name => $foreignKey) {
            if (!$this->current->hasForeignKey($foreignKey->getColumns())) {
                $difference[] = $foreignKey;
            }
        }

        return $difference;
    }

    /**
     * Returns array where each value contain current and initial element state.
     *
     * @return array
     */
    public function alteredForeignKeys(): array
    {
        $difference = [];

        foreach ($this->current->getForeignKeys() as $name => $foreignKey) {
            if (!$this->initial->hasForeignKey($foreignKey->getColumns())) {
                //Added into schema
                continue;
            }

            $initial = $this->initial->findForeignKey($foreignKey->getColumns());
            if (!$foreignKey->compare($initial)) {
                $difference[] = [$foreignKey, $initial];
            }
        }

        return $difference;
    }
}
