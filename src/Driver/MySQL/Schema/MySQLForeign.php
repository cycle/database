<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Driver\MySQL\Schema;

use Spiral\Database\Schema\AbstractForeignKey;

class MySQLForeign extends AbstractForeignKey
{
    /**
     * @param string $table
     * @param string $tablePrefix
     * @param array  $schema
     * @return MySQLForeign
     */
    public static function createInstance(string $table, string $tablePrefix, array $schema): self
    {
        $reference = new self($table, $tablePrefix, $schema['CONSTRAINT_NAME']);

        $reference->column = $schema['COLUMN_NAME'];

        $reference->foreignTable = $schema['REFERENCED_TABLE_NAME'];
        $reference->foreignKey = $schema['REFERENCED_COLUMN_NAME'];

        $reference->deleteRule = $schema['DELETE_RULE'];
        $reference->updateRule = $schema['UPDATE_RULE'];

        return $reference;
    }
}