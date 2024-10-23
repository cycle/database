<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Schema;

use Cycle\Database\Schema\AbstractIndex;

class PostgresIndex extends AbstractIndex
{
    /**
     * @psalm-param non-empty-string $table Table name.
     */
    public static function createInstance(string $table, array $schema): self
    {
        $index = new self($table, $schema['indexname']);
        $index->type = \str_contains($schema['indexdef'], ' UNIQUE ') ? self::UNIQUE : self::NORMAL;

        if (\preg_match('/\(([^)]+)\)/', $schema['indexdef'], $matches)) {
            $columns = \explode(',', $matches[1]);

            foreach ($columns as $column) {
                //Postgres adds quotes to all columns with uppercase letters
                $column = \trim($column, ' "\'');
                [$column, $order] = AbstractIndex::parseColumn($column);

                $index->columns[] = $column;
                if ($order) {
                    $index->sort[$column] = $order;
                }
            }
        }

        return $index;
    }
}
