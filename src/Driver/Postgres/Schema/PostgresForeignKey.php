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

class PostgresForeignKey extends AbstractForeignKey
{
    /**
     * @psalm-param non-empty-string $table
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

    private function normalizeKeys(array $columns): array
    {
        $result = [];
        foreach ($columns as $column) {
            if (!\in_array($column, $result, true)) {
                $result[] = $column;
            }
        }

        return $result;
    }
}
