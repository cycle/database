<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\MySQL\Schema;

use Spiral\Database\Schema\AbstractForeignKey;

class MySQLForeignKey extends AbstractForeignKey
{
    /**
     * @param string $table
     * @param string $tablePrefix
     * @param array  $schema
     * @return MySQLForeignKey
     */
    public static function createInstance(string $table, string $tablePrefix, array $schema): self
    {
        $reference = new self($table, $tablePrefix, $schema['CONSTRAINT_NAME']);

        $reference->columns = $schema['COLUMN_NAME'];
        $reference->foreignTable = $schema['REFERENCED_TABLE_NAME'];
        $reference->foreignKeys = $schema['REFERENCED_COLUMN_NAME'];

        $reference->deleteRule = $schema['DELETE_RULE'];
        $reference->updateRule = $schema['UPDATE_RULE'];

        return $reference;
    }
}
