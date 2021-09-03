<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Schema;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\IndexInterface;
use Cycle\Database\Schema\Traits\ElementTrait;

/**
 * Abstract index schema with read (see IndexInterface) and write abilities. Must be implemented
 * by driver to support DBMS specific syntax and creation rules.
 */
abstract class AbstractIndex implements IndexInterface, ElementInterface
{
    use ElementTrait;

    /**
     * Index types.
     */
    public const NORMAL = 'INDEX';
    public const UNIQUE = 'UNIQUE';

    /**
     * Index type, by default NORMAL and UNIQUE indexes supported, additional types can be
     * implemented on database driver level.
     *
     * @var string
     */
    protected $type = self::NORMAL;

    /**
     * Columns used to form index.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Columns mapping to sorting order
     *
     * @var array
     */
    protected $sort = [];

    /**
     * @param string $table
     * @param string $name
     */
    public function __construct(string $table, string $name)
    {
        $this->table = $table;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function isUnique(): bool
    {
        return $this->type === self::UNIQUE;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * {@inheritdoc}
     */
    public function getSort(): array
    {
        return $this->sort;
    }

    /**
     * Will return columns list with their corresponding order expressions
     */
    public function getColumnsWithSort(): array
    {
        return array_map(function ($column) {
            if ($order = $this->sort[$column] ?? null) {
                return "$column $order";
            }

            return $column;
        }, $this->columns);
    }

    /**
     * Declare index type and behaviour to unique/non-unique state.
     *
     * @param bool $unique
     *
     * @return self
     */
    public function unique(bool $unique = true): self
    {
        $this->type = $unique ? self::UNIQUE : self::NORMAL;

        return $this;
    }

    /**
     * Change set of index forming columns. Method must support both array and string parameters.
     *
     * Example:
     * $index->columns('key');
     * $index->columns('key', 'key2');
     * $index->columns(['key', 'key2']);
     *
     * @param array|string $columns Columns array or comma separated list of parameters.
     *
     * @return self
     */
    public function columns($columns): self
    {
        if (!is_array($columns)) {
            $columns = func_get_args();
        }

        $this->columns = $columns;

        return $this;
    }

    /**
     * Change a columns order mapping if needed.
     *
     * Example:
     * $index->sort(['key2' => 'DESC']);
     *
     * @param array $sort Associative array of columns to sort order.
     *
     * @return self
     */
    public function sort(array $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Index sql creation syntax.
     *
     * @param DriverInterface $driver
     * @param bool            $includeTable Include table ON statement (not required for inline index creation).
     *
     * @return string
     */
    public function sqlStatement(DriverInterface $driver, bool $includeTable = true): string
    {
        $statement = [$this->isUnique() ? 'UNIQUE INDEX' : 'INDEX'];

        $statement[] = $driver->identifier($this->name);

        if ($includeTable) {
            $statement[] = "ON {$driver->identifier($this->table)}";
        }

        //Wrapping column names
        $columns = [];
        foreach ($this->columns as $column) {
            $quoted = $driver->identifier($column);
            if ($order = $this->sort[$column] ?? null) {
                $quoted = "$quoted $order";
            }

            $columns[] = $quoted;
        }
        $columns = implode(', ', $columns);

        $statement[] = "({$columns})";

        return implode(' ', $statement);
    }

    /**
     * @param AbstractIndex $initial
     *
     * @return bool
     */
    public function compare(self $initial): bool
    {
        return $this == clone $initial;
    }

    /**
     * Parse column name and order from column expression
     *
     * @param mixed $column
     *
     * @return array
     */
    public static function parseColumn($column)
    {
        if (is_array($column)) {
            return $column;
        }

        // Contains ASC
        if (substr($column, -4) === ' ASC') {
            return [
                substr($column, 0, strlen($column) - 4),
                'ASC',
            ];
        }
        if (substr($column, -5) === ' DESC') {
            return [
                substr($column, 0, strlen($column) - 5),
                'DESC',
            ];
        }

        return [
            $column,
            null,
        ];
    }
}
