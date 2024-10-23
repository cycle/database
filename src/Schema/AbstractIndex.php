<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     */
    protected string $type = self::NORMAL;

    /**
     * Columns used to form index.
     */
    protected array $columns = [];

    /**
     * Columns mapping to sorting order
     */
    protected array $sort = [];

    /**
     * @psalm-param non-empty-string $table
     * @psalm-param non-empty-string $name
     */
    public function __construct(
        protected string $table,
        protected string $name,
    ) {}

    /**
     * Parse column name and order from column expression
     */
    public static function parseColumn(array|string $column): array
    {
        if (\is_array($column)) {
            return $column;
        }

        // Contains ASC
        if (\str_ends_with($column, ' ASC')) {
            return [
                \substr($column, 0, -4),
                'ASC',
            ];
        }

        if (\str_ends_with($column, ' DESC')) {
            return [
                \substr($column, 0, -5),
                'DESC',
            ];
        }

        return [
            $column,
            null,
        ];
    }

    public function isUnique(): bool
    {
        return $this->type === self::UNIQUE;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getSort(): array
    {
        return $this->sort;
    }

    /**
     * Will return columns list with their corresponding order expressions
     */
    public function getColumnsWithSort(): array
    {
        $self = $this;
        return \array_map(
            static fn(string $column): string => ($order = $self->sort[$column] ?? null) ? "$column $order" : $column,
            $this->columns,
        );
    }

    /**
     * Declare index type and behaviour to unique/non-unique state.
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
     */
    public function columns(string|array $columns): self
    {
        if (!\is_array($columns)) {
            $columns = \func_get_args();
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
     */
    public function sort(array $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Index sql creation syntax.
     *
     * @param bool $includeTable Include table ON statement (not required for inline index creation).
     *
     * @psalm-return non-empty-string
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
        $columns = \implode(', ', $columns);

        $statement[] = "({$columns})";

        return \implode(' ', $statement);
    }

    public function compare(self $initial): bool
    {
        return $this == clone $initial;
    }
}
