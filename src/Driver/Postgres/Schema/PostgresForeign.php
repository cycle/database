<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Driver\Postgres\Schema;

use Spiral\Database\Schema\AbstractForeignKey;

class PostgresForeign extends AbstractForeignKey
{
    /**
     * @param string $table
     * @param string $tablePrefix
     * @param array  $schema
     * @return PostgresForeign
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