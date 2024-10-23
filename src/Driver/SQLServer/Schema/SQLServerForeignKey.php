<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer\Schema;

use Cycle\Database\Schema\AbstractForeignKey;

class SQLServerForeignKey extends AbstractForeignKey
{
    public static function createInstance(string $table, string $tablePrefix, array $schema): self
    {
        $foreign = new self($table, $tablePrefix, $schema['FK_NAME']);

        $foreign->columns = $schema['FKCOLUMN_NAME'];
        $foreign->foreignTable = $schema['PKTABLE_NAME'];
        $foreign->foreignKeys = $schema['PKCOLUMN_NAME'];

        $foreign->deleteRule = $schema['DELETE_RULE'] ? self::NO_ACTION : self::CASCADE;
        $foreign->updateRule = $schema['UPDATE_RULE'] ? self::NO_ACTION : self::CASCADE;

        return $foreign;
    }
}
