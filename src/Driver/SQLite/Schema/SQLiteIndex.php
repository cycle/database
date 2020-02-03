<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\SQLite\Schema;

use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Schema\AbstractIndex;

class SQLiteIndex extends AbstractIndex
{
    /**
     * @param string $table
     * @param array  $schema
     * @param array  $columns
     * @param array  $fallbackColumns
     * @return SQLiteIndex
     */
    public static function createInstance(
        string $table,
        array $schema,
        array $columns,
        array $fallbackColumns
    ): self {
        $index = new self($table, $schema['name']);
        $index->type = $schema['unique'] ? self::UNIQUE : self::NORMAL;

        if ($columns !== []) {
            foreach ($columns as $column) {
                // We only need key columns
                if (intval($column['cid']) > -1) {
                    $index->columns[] = $column['name'];
                    if (intval($column['desc']) === 1) {
                        $index->sort[$column['name']] = 'DESC';
                    }
                }
            }
        } else {
            // use legacy format
            foreach ($fallbackColumns as $column) {
                $index->columns[] = $column['name'];
            }
        }

        return $index;
    }

    /**
     * @inheritdoc
     */
    public function sqlStatement(DriverInterface $driver, bool $includeTable = true): string
    {
        $statement = [$this->isUnique() ? 'UNIQUE INDEX' : 'INDEX'];

        //SQLite love to add indexes without being asked for that
        $statement[] = 'IF NOT EXISTS';
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
}
