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
 * TableSchema helper used to store original table elements and run comparation between them.
 *
 * Attention: this state IS MUTABLE!
 */
final class State
{
    /** @var string */
    private $name;

    /** @var AbstractColumn[] */
    private $columns = [];

    /** @var AbstractIndex[] */
    private $indexes = [];

    /** @var AbstractForeignKey[] */
    private $foreignKeys = [];

    /**
     * Primary key columns are stored separately from other indexes and
     * can only be modified during table creation.
     *
     * @var array
     */
    private $primaryKeys = [];

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Cloning all elements.
     */
    public function __clone()
    {
        foreach ($this->columns as $name => $column) {
            $this->columns[$name] = clone $column;
        }

        foreach ($this->indexes as $name => $index) {
            $this->indexes[$name] = clone $index;
        }

        foreach ($this->foreignKeys as $name => $foreignKey) {
            $this->foreignKeys[$name] = clone $foreignKey;
        }
    }

    /**
     * Set table name. Operation will be applied at moment of saving.
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return AbstractColumn[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return AbstractIndex[]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * @return AbstractForeignKey[]
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * @param array $columns
     */
    public function setPrimaryKeys(array $columns): void
    {
        $this->primaryKeys = $columns;
    }

    /**
     * Method combines primary keys with primary keys automatically calculated based on registered columns.
     *
     * @return array
     */
    public function getPrimaryKeys(): array
    {
        $primaryColumns = [];
        foreach ($this->getColumns() as $column) {
            $type = $column->getAbstractType();
            if ($type === 'primary' || $type === 'bigPrimary') {
                if (!in_array($column->getName(), $this->primaryKeys, true)) {
                    //Only columns not listed as primary keys already
                    $primaryColumns[] = $column->getName();
                }
            }
        }

        return array_unique(array_merge($this->primaryKeys, $primaryColumns));
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasColumn(string $name): bool
    {
        return $this->findColumn($name) !== null;
    }

    /**
     * @param array $columns
     * @return bool
     */
    public function hasIndex(array $columns = []): bool
    {
        return $this->findIndex($columns) !== null;
    }

    /**
     * @param array $columns
     * @return bool
     */
    public function hasForeignKey(array $columns): bool
    {
        return $this->findForeignKey($columns) !== null;
    }

    /**
     * @param AbstractColumn $column
     */
    public function registerColumn(AbstractColumn $column): void
    {
        $this->columns[$column->getName()] = $column;
    }

    /**
     * @param AbstractIndex $index
     */
    public function registerIndex(AbstractIndex $index): void
    {
        $this->indexes[$index->getName()] = $index;
    }

    /**
     * @param AbstractForeignKey $reference
     */
    public function registerForeignKey(AbstractForeignKey $reference): void
    {
        $this->foreignKeys[$reference->getName()] = $reference;
    }

    /**
     * Drop column from table schema.
     *
     * @param AbstractColumn $column
     * @return self
     */
    public function forgetColumn(AbstractColumn $column): State
    {
        foreach ($this->columns as $name => $columnSchema) {
            // todo: need better compare
            if ($columnSchema == $column) {
                unset($this->columns[$name]);
                break;
            }
        }

        return $this;
    }

    /**
     * Drop index from table schema using it's name or forming columns.
     *
     * @param AbstractIndex $index
     */
    public function forgetIndex(AbstractIndex $index): void
    {
        foreach ($this->indexes as $name => $indexSchema) {
            // todo: need better compare
            if ($indexSchema == $index) {
                unset($this->indexes[$name]);
                break;
            }
        }
    }

    /**
     * Drop foreign key from table schema using it's forming column.
     *
     * @param AbstractForeignKey $foreignKey
     */
    public function forgerForeignKey(AbstractForeignKey $foreignKey): void
    {
        foreach ($this->foreignKeys as $name => $foreignSchema) {
            // todo: need better compare
            if ($foreignSchema == $foreignKey) {
                unset($this->foreignKeys[$name]);
                break;
            }
        }
    }

    /**
     * @param string $name
     * @return null|AbstractColumn
     */
    public function findColumn(string $name): ?AbstractColumn
    {
        foreach ($this->columns as $column) {
            if ($column->getName() === $name) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Find index by it's columns or return null.
     *
     * @param array $columns
     * @return null|AbstractIndex
     */
    public function findIndex(array $columns): ?AbstractIndex
    {
        foreach ($this->indexes as $index) {
            if ($index->getColumnsWithSort() === $columns) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Find foreign key by it's column or return null.
     *
     * @param array $columns
     * @return null|AbstractForeignKey
     */
    public function findForeignKey(array $columns): ?AbstractForeignKey
    {
        foreach ($this->foreignKeys as $fk) {
            if ($fk->getColumns() === $columns) {
                return $fk;
            }
        }

        return null;
    }

    /**
     * Remount elements under their current name.
     */
    public function remountElements(): void
    {
        $columns = [];
        foreach ($this->columns as $column) {
            $columns[$column->getName()] = $column;
        }

        $indexes = [];
        foreach ($this->indexes as $index) {
            $indexes[$index->getName()] = $index;
        }

        $foreignKeys = [];
        foreach ($this->foreignKeys as $fk) {
            $foreignKeys[$fk->getName()] = $fk;
        }

        $this->columns = $columns;
        $this->indexes = $indexes;
        $this->foreignKeys = $foreignKeys;
    }

    /**
     * Re-populate schema elements using other state as source. Elements will be cloned under their
     * schema name.
     *
     * @param State $source
     *
     * @return self
     */
    public function syncState(State $source): self
    {
        $this->name = $source->name;
        $this->primaryKeys = $source->primaryKeys;

        $this->columns = [];
        foreach ($source->columns as $name => $column) {
            $this->columns[$name] = clone $column;
        }

        $this->indexes = [];
        foreach ($source->indexes as $name => $index) {
            $this->indexes[$name] = clone $index;
        }

        $this->foreignKeys = [];
        foreach ($source->foreignKeys as $name => $foreignKey) {
            $this->foreignKeys[$name] = clone $foreignKey;
        }

        $this->remountElements();

        return $this;
    }
}
