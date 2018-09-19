<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Schema;

use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractIndex;
use Spiral\Database\Schema\AbstractReference;

/**
 * TableSchema helper used to store original table elements and run comparation between them.
 *
 * Attention: this state IS MUTABLE!
 */
class TableState
{
    /**
     * @var string
     */
    private $name = '';

    /**
     * @var AbstractColumn[]
     */
    private $columns = [];

    /**
     * @var AbstractIndex[]
     */
    private $indexes = [];

    /**
     * @var AbstractReference[]
     */
    private $foreigns = [];

    /**
     * Primary key columns are stored separately from other indexes and can be modified only during
     * table creation.
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     *
     * Array key points to initial element name.
     *
     * @return AbstractColumn[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * {@inheritdoc}
     *
     * Array key points to initial element name.
     *
     * @return AbstractIndex[]
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * {@inheritdoc}
     *
     * Array key points to initial element name.
     *
     * @return AbstractReference[]
     */
    public function getForeigns()
    {
        return $this->foreigns;
    }

    /**
     * Set table primary keys.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function setPrimaryKeys(array $columns)
    {
        $this->primaryKeys = $columns;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Method combines primary keys with primary keys automatically calculated based on registred
     * columns.
     */
    public function getPrimaryKeys()
    {
        $primaryColumns = [];
        foreach ($this->getColumns() as $column) {
            if ($column->abstractType() == 'primary' || $column->abstractType() == 'bigPrimary') {
                if (!in_array($column->getName(), $this->primaryKeys)) {
                    //Only columns not listed as primary keys already
                    $primaryColumns[] = $column->getName();
                }
            }
        }

        return array_unique(array_merge($this->primaryKeys, $primaryColumns));
    }

    /**
     * {@inheritdoc}
     *
     * Lookup is performed based on initial column name.
     */
    public function hasColumn(string $name): bool
    {
        return !empty($this->findColumn($name));
    }

    /**
     * {@inheritdoc}
     */
    public function hasIndex(array $columns = []): bool
    {
        return !empty($this->findIndex($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function hasForeign($column): bool
    {
        return !empty($this->findForeign($column));
    }

    /**
     * Register new column element.
     *
     * @param AbstractColumn $column
     *
     * @return AbstractColumn
     */
    public function registerColumn(AbstractColumn $column): AbstractColumn
    {
        $this->columns[$column->getName()] = $column;

        return $column;
    }

    /**
     * Register new index element.
     *
     * @param AbstractIndex $index
     *
     * @return AbstractIndex
     */
    public function registerIndex(AbstractIndex $index): AbstractIndex
    {
        $this->indexes[$index->getName()] = $index;

        return $index;
    }

    /**
     * Register new foreign key element.
     *
     * @param AbstractReference $foreign
     *
     * @return AbstractReference
     */
    public function registerForeign(AbstractReference $foreign): AbstractReference
    {
        $this->foreigns[$foreign->getName()] = $foreign;

        return $foreign;
    }

    /**
     * Drop column from table schema.
     *
     * @param AbstractColumn $column
     *
     * @return self
     */
    public function forgetColumn(AbstractColumn $column): TableState
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
     *
     * @return self
     */
    public function forgetIndex(AbstractIndex $index): TableState
    {
        foreach ($this->indexes as $name => $indexSchema) {
            if ($indexSchema == $index) {
                unset($this->indexes[$name]);
                break;
            }
        }

        return $this;
    }

    /**
     * Drop foreign key from table schema using it's forming column.
     *
     * @param AbstractReference $foreign
     *
     * @return self
     */
    public function forgetForeign(AbstractReference $foreign): TableState
    {
        foreach ($this->foreigns as $name => $foreignSchema) {
            if ($foreignSchema == $foreign) {
                unset($this->foreigns[$name]);
                break;
            }
        }

        return $this;
    }

    /**
     * Find column by it's name or return null.
     *
     * @param string $name
     *
     * @return null|AbstractColumn
     */
    public function findColumn(string $name)
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
     *
     * @return null|AbstractIndex
     */
    public function findIndex(array $columns)
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
     *
     * @return null|AbstractReference
     */
    public function findForeign(string $column)
    {
        foreach ($this->foreigns as $reference) {
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

        $references = [];
        foreach ($this->foreigns as $reference) {
            $references[$reference->getName()] = $reference;
        }

        $this->columns = $columns;
        $this->indexes = $indexes;
        $this->foreigns = $references;
    }

    /**
     * Re-populate schema elements using other state as source. Elements will be cloned under their
     * schema name.
     *
     * @param TableState $source
     *
     * @return self
     */
    public function syncState(TableState $source): self
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

        $this->foreigns = [];
        foreach ($source->foreigns as $name => $reference) {
            $this->foreigns[$name] = clone $reference;
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

        foreach ($this->foreigns as $name => $foreign) {
            $this->foreigns[$name] = clone $foreign;
        }
    }
}
