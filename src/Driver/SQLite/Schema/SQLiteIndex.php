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
     * @return SQLiteIndex
     */
    public static function createInstance(string $table, array $schema, array $columns): self
    {
        $index = new self($table, $schema['name']);
        $index->type = $schema['unique'] ? self::UNIQUE : self::NORMAL;

        foreach ($columns as $column) {
            $index->columns[] = $column['name'];
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
        $columns = implode(', ', array_map([$driver, 'identifier'], $this->columns));

        $statement[] = "({$columns})";

        return implode(' ', $statement);
    }
}
