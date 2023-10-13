<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL\Injection;

use Cycle\Database\Driver\Quoter;
use Cycle\Database\Injection\JsonExpression;

class CompileJsonDoesntContain extends JsonExpression
{
    protected function compile(string $statement): string
    {
        $quoter = new Quoter('', '``');

        $parts = \explode('->', $statement, 2);
        $field = $quoter->quote($parts[0]);
        $path = \count($parts) > 1 ? ', ' . $this->wrapPath($parts[1]) : '';

        return 'NOT json_contains(' . $field . ', ?' . $path . ')';
    }
}
