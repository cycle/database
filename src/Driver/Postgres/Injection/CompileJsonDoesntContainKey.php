<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Injection;

class CompileJsonDoesntContainKey extends CompileJsonContainsKey
{
    protected function compile(string $statement): string
    {
        return 'NOT ' . parent::compile($statement);
    }
}
