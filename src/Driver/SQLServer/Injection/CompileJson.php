<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer\Injection;

class CompileJson extends SQLServerJsonExpression
{
    protected function compile(string $statement): string
    {
        return 'json_value(' . $this->getField($statement) . $this->getPath($statement) . ')';
    }
}
