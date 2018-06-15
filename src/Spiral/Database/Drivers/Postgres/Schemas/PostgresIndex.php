<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Drivers\Postgres\Schemas;

use Spiral\Database\Schemas\Prototypes\AbstractIndex;

class PostgresIndex extends AbstractIndex
{
    /**
     * @param string $table Table name.
     * @param array  $schema
     *
     * @return PostgresIndex
     */
    public static function createInstance(string $table, array $schema): self
    {
        $index = new self($table, $schema['indexname']);
        $index->type = strpos($schema['indexdef'], ' UNIQUE ') ? self::UNIQUE : self::NORMAL;

        if (preg_match('/\(([^)]+)\)/', $schema['indexdef'], $matches)) {
            $columns = explode(',', $matches[1]);

            foreach ($columns as $column) {
                //Postgres adds quotes to all columns with uppercase letters
                $index->columns[] = trim($column, ' "\'');
            }
        }

        return $index;
    }
}