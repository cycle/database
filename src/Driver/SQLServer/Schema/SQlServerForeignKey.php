<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer\Schema;

use Cycle\Database\Schema\AbstractForeignKey;

class SQlServerForeignKey extends AbstractForeignKey
{
    /**
     * @param string $table
     * @param string $tablePrefix
     * @param array  $schema
     *
     * @return SQlServerForeignKey
     */
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
