<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Schema;

use Cycle\Database\Schema\AbstractForeignKey;
use Spiral\Database\Driver\Postgres\Schema\PostgresForeignKey as SpiralPostgresForeignKey;

class PostgresForeignKey extends AbstractForeignKey
{
    /**
     * @param string $table
     * @param string $tablePrefix
     * @param array  $schema
     * @return PostgresForeignKey
     */
    public static function createInstance(string $table, string $tablePrefix, array $schema): self
    {
        $foreign = new self($table, $tablePrefix, $schema['constraint_name']);

        $foreign->columns = $foreign->normalizeKeys($schema['column_name']);
        $foreign->foreignTable = $schema['foreign_table_name'];
        $foreign->foreignKeys = $foreign->normalizeKeys($schema['foreign_column_name']);

        $foreign->deleteRule = $schema['delete_rule'];
        $foreign->updateRule = $schema['update_rule'];

        return $foreign;
    }

    /**
     * @param array $columns
     * @return array
     */
    private function normalizeKeys(array $columns): array
    {
        $result = [];
        foreach ($columns as $column) {
            if (array_search($column, $result, true) === false) {
                $result[] = $column;
            }
        }

        return $result;
    }
}
\class_alias(PostgresForeignKey::class, SpiralPostgresForeignKey::class, false);
