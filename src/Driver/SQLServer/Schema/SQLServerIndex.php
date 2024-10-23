<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer\Schema;

use Cycle\Database\Schema\AbstractIndex;

class SQLServerIndex extends AbstractIndex
{
    /**
     * @psalm-param non-empty-string $table
     */
    public static function createInstance(string $table, array $schema): self
    {
        //Schema is basically array of index columns merged with index meta
        $index = new self($table, \current($schema)['indexName']);
        $index->type = \current($schema)['isUnique'] ? self::UNIQUE : self::NORMAL;

        foreach ($schema as $indexColumn) {
            $index->columns[] = $indexColumn['columnName'];
            if ((int) $indexColumn['isDescendingKey'] === 1) {
                $index->sort[$indexColumn['columnName']] = 'DESC';
            }
        }

        return $index;
    }
}
