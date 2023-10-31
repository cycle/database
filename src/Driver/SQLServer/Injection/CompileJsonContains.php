<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer\Injection;

class CompileJsonContains extends SQLServerJsonExpression
{
    protected function compile(string $statement): string
    {
        return '? IN (SELECT [value] FROM openjson(' . $this->getField($statement) . $this->getPath($statement) . '))';
    }
}
