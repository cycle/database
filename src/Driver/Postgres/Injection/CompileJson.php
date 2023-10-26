<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Injection;

class CompileJson extends PostgresJsonExpression
{
    protected function compile(string $statement): string
    {
        $path = $this->getPath($statement);

        if (!empty($path)) {
            return \sprintf('%s->%s->>%s', $this->getField($statement), $path, $this->getAttribute($statement));
        }

        return \sprintf('%s->>%s', $this->getField($statement), $this->getAttribute($statement));
    }
}
