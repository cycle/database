<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL\Injection;

class CompileJsonContainsKey extends MySQLJsonExpression
{
    protected function compile(string $statement): string
    {
        return \sprintf(
            'IFNULL(json_contains_path(%s, \'one\'%s), 0)',
            $this->getField($statement),
            $this->getPath($statement),
        );
    }
}
