<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Driver\SQLite\Schema;

use Spiral\Database\Driver;
use Spiral\Database\Schema\AbstractIndex;

class SQLiteIndex extends AbstractIndex
{
    /**
     * @param string $table
     * @param array  $schema
     * @param array  $columns
     *
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
     * Index sql creation syntax.
     *
     * @param Driver $driver
     * @param bool   $includeTable Include table ON statement (not required for inline index
     *                             creation).
     *
     * @return string
     */
    public function sqlStatement(Driver $driver, bool $includeTable = true): string
    {
        $statement = [$this->type == self::UNIQUE ? 'UNIQUE INDEX' : 'INDEX'];

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
