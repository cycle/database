<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Schema;

use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\IndexInterface;
use Spiral\Database\Schema\Traits\ElementTrait;

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
     * Declare index type and behaviour to unique/non-unique state.
     *
     * @param bool $unique
     * @return self
     */
    public function unique(bool $unique = true): AbstractIndex
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
     * @param string|array $columns Columns array or comma separated list of parameters.
     * @return self
     */
    public function columns($columns): AbstractIndex
    {
        if (!is_array($columns)) {
            $columns = func_get_args();
        }

        $this->columns = $columns;

        return $this;
    }

    /**
     * Index sql creation syntax.
     *
     * @param DriverInterface $driver
     * @param bool            $includeTable Include table ON statement (not required for inline index creation).
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
        $columns = implode(', ', array_map([$driver, 'identifier'], $this->columns));

        $statement[] = "({$columns})";

        return implode(' ', $statement);
    }

    /**
     * @param AbstractIndex $initial
     * @return bool
     */
    public function compare(AbstractIndex $initial): bool
    {
        return $this == clone $initial;
    }
}
