<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Driver\Postgres\Schema;

use Spiral\Database\Schema\Prototypes\AbstractReference;

class PostgresReference extends AbstractReference
{
    /**
     * @param string $table
     * @param string $tablePrefix
     * @param array  $schema
     *
     * @return PostgresReference
     */
    public static function createInstance(string $table, string $tablePrefix, array $schema): self
    {
        $foreign = new self($table, $tablePrefix, $schema['constraint_name']);

        $foreign->column = $schema['column_name'];

        $foreign->foreignTable = $schema['foreign_table_name'];
        $foreign->foreignKey = $schema['foreign_column_name'];

        $foreign->deleteRule = $schema['delete_rule'];
        $foreign->updateRule = $schema['update_rule'];

        return $foreign;
    }
}