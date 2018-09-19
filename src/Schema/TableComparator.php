<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Schema;

/**
 * Compares two table states.
 */
class TableComparator
{
    /**
     * @var TableState
     */
    private $initial = null;

    /**
     * @var TableState
     */
    private $current = null;

    /**
     * @param TableState $initial
     * @param TableState $current
     */
    public function __construct(TableState $initial, TableState $current)
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
            count($this->addedForeigns()),
            count($this->droppedForeigns()),
            count($this->alteredForeigns()),
        ];

        return array_sum($difference) != 0;
    }

    /**
     * @return bool
     */
    public function isRenamed(): bool
    {
        return $this->current->getName() != $this->initial->getName();
    }

    /**
     * @return bool
     */
    public function isPrimaryChanged(): bool
    {
        return $this->current->getPrimaryKeys() != $this->initial->getPrimaryKeys();
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
     * @return AbstractReference[]
     */
    public function addedForeigns(): array
    {
        $difference = [];
        foreach ($this->current->getForeigns() as $name => $foreign) {
            if (!$this->initial->hasForeign($foreign->getColumn())) {
                $difference[] = $foreign;
            }
        }

        return $difference;
    }

    /**
     * @return AbstractReference[]
     */
    public function droppedForeigns(): array
    {
        $difference = [];
        foreach ($this->initial->getForeigns() as $name => $foreign) {
            if (!$this->current->hasForeign($foreign->getColumn())) {
                $difference[] = $foreign;
            }
        }

        return $difference;
    }

    /**
     * Returns array where each value contain current and initial element state.
     *
     * @return array
     */
    public function alteredForeigns(): array
    {
        $difference = [];

        foreach ($this->current->getForeigns() as $name => $foreign) {
            if (!$this->initial->hasForeign($foreign->getColumn())) {
                //Added into schema
                continue;
            }

            $initial = $this->initial->findForeign($foreign->getColumn());
            if (!$foreign->compare($initial)) {
                $difference[] = [$foreign, $initial];
            }
        }

        return $difference;
    }
}
