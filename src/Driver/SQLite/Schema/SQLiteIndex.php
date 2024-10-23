<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLite\Schema;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Schema\AbstractIndex;

class SQLiteIndex extends AbstractIndex
{
    public static function createInstance(
        string $table,
        array $schema,
        array $columns,
        array $fallbackColumns,
    ): self {
        $index = new self($table, $schema['name']);
        $index->type = $schema['unique'] ? self::UNIQUE : self::NORMAL;

        if ($columns !== []) {
            foreach ($columns as $column) {
                // We only need key columns
                if ((int) $column['cid'] > -1) {
                    $index->columns[] = $column['name'];
                    if ((int) $column['desc'] === 1) {
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
        $columns = \implode(', ', $columns);

        $statement[] = "({$columns})";

        return \implode(' ', $statement);
    }
}
