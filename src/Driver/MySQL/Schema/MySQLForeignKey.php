<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL\Schema;

use Cycle\Database\Schema\AbstractForeignKey;

class MySQLForeignKey extends AbstractForeignKey
{
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

    public function hasIndex(): bool
    {
        return true;
    }
}
