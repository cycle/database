<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL\Schema;

use Cycle\Database\Schema\AbstractIndex;

class MySQLIndex extends AbstractIndex
{
    public static function createInstance(string $table, string $name, array $schema): self
    {
        $index = new self($table, $name);

        foreach ($schema as $definition) {
            $index->type = $definition['Non_unique'] ? self::NORMAL : self::UNIQUE;
            $index->columns[] = $definition['Column_name'];
            if ($definition['Collation'] === 'D') {
                $index->sort[$definition['Column_name']] = 'DESC';
            }
        }

        return $index;
    }
}
