<?php

declare(strict_types=1);

namespace Cycle\Database\Driver\Oracle\Schema;

use Cycle\Database\Schema\AbstractIndex;

class OracleIndex extends AbstractIndex
{
    public static function createInstance(string $table, string $name, array $schema): self
    {
        $index = new self($table, $name);

        foreach ($schema as $definition) {
            $index->type = $definition['UNIQUENESS'] === 'UNIQUE' ? self::UNIQUE : self::NORMAL;
            $index->columns[] = $definition['COLUMN_NAME'];
            if ($definition['DESCEND'] !== 'ASC') {
                $index->sort[$definition['COLUMN_NAME']] = 'DESC';
            }
        }

        return $index;
    }
}
