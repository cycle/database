<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database;

use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Query\DeleteQuery;
use Spiral\Database\Query\InsertQuery;
use Spiral\Database\Query\SelectQuery;
use Spiral\Database\Query\UpdateQuery;
use Spiral\Database\Schema\AbstractTable;

/**
 * Represent table level abstraction with simplified access to SelectQuery associated with such
 * table.
 *
 * @method int avg($identifier) Perform aggregation (AVG) based on column or expression value.
 * @method int min($identifier) Perform aggregation (MIN) based on column or expression value.
 * @method int max($identifier) Perform aggregation (MAX) based on column or expression value.
 * @method int sum($identifier) Perform aggregation (SUM) based on column or expression value.
 */
final class Table implements TableInterface, \IteratorAggregate, \Countable
{
    /** @var DatabaseInterface */
    protected $database;

    /** @var string */
    private $name;

    /**
     * @param DatabaseInterface $database Parent DBAL database.
     * @param string            $name     Table name without prefix.
     */
    public function __construct(DatabaseInterface $database, string $name)
    {
        $this->name = $name;
        $this->database = $database;
    }

    /**
     * Bypass call to SelectQuery builder.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return SelectQuery|mixed
     */
    public function __call($method, array $arguments)
    {
        return call_user_func_array([$this->select(), $method], $arguments);
    }

    /**
     * Get associated database.
     *
     * @return Database
     */
    public function getDatabase(): DatabaseInterface
    {
        return $this->database;
    }

    /**
     * Real table name, will include database prefix.
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->database->getPrefix() . $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get modifiable table schema.
     *
     * @return AbstractTable
     */
    public function getSchema(): AbstractTable
    {
        return $this->database
            ->getDriver(DatabaseInterface::WRITE)
            ->getSchemaHandler()
            ->getSchema(
                $this->name,
                $this->database->getPrefix()
            );
    }

    /**
     * Erase all table data.
     */
    public function eraseData(): void
    {
        $this->database
            ->getDriver(DatabaseInterface::WRITE)
            ->getSchemaHandler()
            ->eraseTable($this->getSchema());
    }

    /**
     * Insert one fieldset into table and return last inserted id.
     *
     * Example:
     * $table->insertOne(["name" => "Wolfy-J", "balance" => 10]);
     *
     * @param array $rowset
     * @return int|string|null
     *
     * @throws BuilderException
     */
    public function insertOne(array $rowset = [])
    {
        return $this->database
            ->insert($this->name)
            ->values($rowset)
            ->run();
    }

    /**
     * Perform batch insert into table, every rowset should have identical amount of values matched
     * with column names provided in first argument. Method will return lastInsertID on success.
     *
     * Example:
     * $table->insertMultiple(["name", "balance"], array(["Bob", 10], ["Jack", 20]))
     *
     * @param array $columns Array of columns.
     * @param array $rowsets Array of rowsets.
     */
    public function insertMultiple(array $columns = [], array $rowsets = []): void
    {
        //No return value
        $this->database
            ->insert($this->name)
            ->columns($columns)
            ->values($rowsets)
            ->run();
    }

    /**
     * Get insert builder specific to current table.
     *
     * @return InsertQuery
     */
    public function insert(): InsertQuery
    {
        return $this->database
            ->insert($this->name);
    }

    /**
     * Get SelectQuery builder with pre-populated from tables.
     *
     * @param string $columns
     *
     * @return SelectQuery
     */
    public function select($columns = '*'): SelectQuery
    {
        return $this->database
            ->select(func_num_args() ? func_get_args() : '*')
            ->from($this->name);
    }

    /**
     * Get DeleteQuery builder with pre-populated table name. This is NOT table delete method, use
     * schema()->drop() for this purposes. If you want to remove all records from table use
     * Table->truncate() method. Call ->run() to perform query.
     *
     * @param array $where Initial set of where rules specified as array.
     *
     * @return DeleteQuery
     */
    public function delete(array $where = []): DeleteQuery
    {
        return $this->database
            ->delete($this->name, $where);
    }

    /**
     * Get UpdateQuery builder with pre-populated table name and set of columns to update. Columns
     * can be scalar values, Parameter objects or even SQLFragments. Call ->run() to perform query.
     *
     * @param array $values Initial set of columns associated with values.
     * @param array $where  Initial set of where rules specified as array.
     *
     * @return UpdateQuery
     */
    public function update(array $values = [], array $where = []): UpdateQuery
    {
        return $this->database
            ->update($this->name, $values, $where);
    }

    /**
     * Count number of records in table.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->select()->count();
    }

    /**
     * Retrieve an external iterator, SelectBuilder will return PDOResult as iterator.
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @return SelectQuery
     */
    public function getIterator(): SelectQuery
    {
        return $this->select();
    }

    /**
     * A simple alias for table query without condition (returns array of rows).
     *
     * @return array
     */
    public function fetchAll(): array
    {
        return $this->select()->fetchAll();
    }

    /**
     * @inheritdoc
     */
    public function exists(): bool
    {
        return $this->getSchema()->exists();
    }

    /**
     * Array of columns dedicated to primary index. Attention, this methods will ALWAYS return
     * array, even if there is only one primary key.
     *
     * @return array
     */
    public function getPrimaryKeys(): array
    {
        return $this->getSchema()->getPrimaryKeys();
    }

    /**
     * Check if table have specified column.
     *
     * @param string $name Column name.
     * @return bool
     */
    public function hasColumn(string $name): bool
    {
        return $this->getSchema()->hasColumn($name);
    }

    /**
     * Get all declared columns.
     *
     * @return ColumnInterface[]
     */
    public function getColumns(): array
    {
        return $this->getSchema()->getColumns();
    }

    /**
     * Check if table has index related to set of provided columns. Columns order does matter!
     *
     * @param array $columns
     * @return bool
     */
    public function hasIndex(array $columns = []): bool
    {
        return $this->getSchema()->hasIndex($columns);
    }

    /**
     * Get all table indexes.
     *
     * @return IndexInterface[]
     */
    public function getIndexes(): array
    {
        return $this->getSchema()->getIndexes();
    }

    /**
     * Check if table has foreign key related to table column.
     *
     * @param array $columns Column names.
     * @return bool
     */
    public function hasForeignKey(array $columns): bool
    {
        return $this->getSchema()->hasForeignKey($columns);
    }

    /**
     * Get all table foreign keys.
     *
     * @return ForeignKeyInterface[]
     */
    public function getForeignKeys(): array
    {
        return $this->getSchema()->getForeignKeys();
    }

    /**
     * Get list of table names current schema depends on, must include every table linked using
     * foreign key or other constraint. Table names MUST include prefixes.
     *
     * @return array
     */
    public function getDependencies(): array
    {
        return $this->getSchema()->getDependencies();
    }
}
