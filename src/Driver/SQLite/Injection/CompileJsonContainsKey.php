<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLite\Injection;

class CompileJsonContainsKey extends SQLiteJsonExpression
{
    /**
     * @param non-empty-string $statement
     *
     * @return non-empty-string
     */
    protected function compile(string $statement): string
    {
        return \sprintf('json_type(%s%s) IS NOT null', $this->getField($statement), $this->getPath($statement));
    }
}
