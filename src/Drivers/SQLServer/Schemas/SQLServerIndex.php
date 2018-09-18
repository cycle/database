<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Drivers\SQLServer\Schemas;

use Spiral\Database\Schemas\Prototypes\AbstractIndex;

class SQLServerIndex extends AbstractIndex
{
    /**
     * @param string $table Table name.
     * @param array  $schema
     *
     * @return SQLServerIndex
     */
    public static function createInstance(string $table, array $schema): self
    {
        //Schema is basically array of index columns merged with index meta
        $index = new self($table, current($schema)['indexName']);
        $index->type = current($schema)['isUnique'] ? self::UNIQUE : self::NORMAL;

        foreach ($schema as $indexColumn) {
            $index->columns[] = $indexColumn['columnName'];
        }

        return $index;
    }
}