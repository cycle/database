<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLite\Injection;

use Cycle\Database\Injection\JsonExpression;

class CompileJson extends JsonExpression
{
    protected function compile(string $statement): string
    {
        return 'json_extract(' . $this->getField($statement) . $this->getPath($statement) . ')';
    }
}
