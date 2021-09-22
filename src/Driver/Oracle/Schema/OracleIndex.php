<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Oracle\Schema;

use Cycle\Database\Driver\Oracle\Query\OracleSelectQuery;
use Cycle\Database\Schema\AbstractIndex;

class OracleIndex extends AbstractIndex
{
    /**
     * @param string $table Table name.
     * @param array  $schema
     * @return OracleIndex
     */
    public static function createInstance(string $table, array $schema): self
    {
        $index = new self($table, $schema['indexname']);
        $index->type = strpos($schema['indexdef'], ' UNIQUE ') ? self::UNIQUE : self::NORMAL;

        if (preg_match('/\(([^)]+)\)/', $schema['indexdef'], $matches)) {
            $columns = explode(',', $matches[1]);

            foreach ($columns as $column) {
                // Oracle adds quotes to all columns with uppercase letters
                $column = trim($column, ' "\'');
                [$column, $order] = AbstractIndex::parseColumn($column);

                $index->columns[] = $column;
                if ($order) {
                    $index->sort[$column] = $order;
                }
            }
        }

        return $index;
    }
}
