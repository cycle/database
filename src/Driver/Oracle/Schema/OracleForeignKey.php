<?php

declare(strict_types=1);

namespace Cycle\Database\Driver\Oracle\Schema;

use Cycle\Database\Schema\AbstractForeignKey;

class OracleForeignKey extends AbstractForeignKey
{
    public static function createInstance(string $table, string $tablePrefix, array $schema): self
    {
        $reference = new self($table, $tablePrefix, $schema['CONSTRAINT_NAME']);

        $reference->columns = $schema['COLUMN_NAME'];
        $reference->foreignTable = $schema['r_table_name'];
        $reference->foreignKeys = $schema['r_column_name'];

        $reference->deleteRule = $schema['DELETE_RULE'];

        return $reference;
    }
}
