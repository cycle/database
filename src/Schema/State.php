<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Schema;

/**
 * TableSchema helper used to store original table elements and run comparation between them.
 *
 * Attention: this state IS MUTABLE!
 */
final class State
{
    /** @var string */
    private $name = '';

    /** @var AbstractColumn[] */
    private $columns = [];

    /** @var AbstractIndex[] */
    private $indexes = [];

    /** @var AbstractForeignKey[] */
    private $foreignKeys = [];

    /**
     * Primary key columns are stored separately from other indexes and can only be modified during table creation.
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
     * Set table name. Operation will be applied at moment of saving.
     *
     * @param string $name
     */
    public function setName(string $name)
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
    public function setPrimaryKeys(array $columns)
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
            if ($column->getAbstractType() == 'primary' || $column->getAbstractType() == 'bigPrimary') {
                if (!in_array($column->getName(), $this->primaryKeys)) {
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
        return !empty($this->findColumn($name));
    }

    /**
     * @param array $columns
     * @return bool
     */
    public function hasIndex(array $columns = []): bool
    {
        return !empty($this->findIndex($columns));
    }

    /**
     * @param string $column
     * @return bool
     */
    public function hasForeignKey(string $column): bool
    {
        return !empty($this->findForeignKey($column));
    }

    /**
     * @param AbstractColumn $column
     */
    public function registerColumn(AbstractColumn $column)
    {
        $this->columns[$column->getName()] = $column;
    }

    /**
     * @param AbstractIndex $index
     */
    public function registerIndex(AbstractIndex $index)
    {
        $this->indexes[$index->getName()] = $index;
    }

    /**
     * @param AbstractForeignKey $reference
     */
    public function registerForeignKey(AbstractForeignKey $reference)
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
    public function forgetIndex(AbstractIndex $index)
    {
        foreach ($this->indexes as $name => $indexSchema) {
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
    public function forgerForeignKey(AbstractForeignKey $foreignKey)
    {
        foreach ($this->foreignKeys as $name => $foreignSchema) {
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
            if ($column->getName() == $name) {
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
            if ($index->getColumns() == $columns) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Find foreign key by it's column or return null.
     *
     * @param string $column
     * @return null|AbstractForeignKey
     */
    public function findForeignKey(string $column): ?AbstractForeignKey
    {
        foreach ($this->foreignKeys as $reference) {
            if ($reference->getColumn() == $column) {
                return $reference;
            }
        }

        return null;
    }

    /**
     * Remount elements under their current name.
     */
    public function remountElements()
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
        foreach ($this->foreignKeys as $foreignKey) {
            $foreignKeys[$foreignKey->getName()] = $foreignKey;
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
}
