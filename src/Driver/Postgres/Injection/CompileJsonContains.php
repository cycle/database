<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Injection;

class CompileJsonContains extends PostgresJsonExpression
{
    /**
     * @param non-empty-string $statement
     *
     * @return non-empty-string
     */
    protected function compile(string $statement): string
    {
        $path = $this->getPath($statement);
        $field = $this->getField($statement);
        $attribute = $this->getAttribute($statement);

        if (!empty($path)) {
            return \sprintf('(%s->%s->%s)::jsonb @> ?', $field, $path, $attribute);
        }

        return \sprintf('(%s->%s)::jsonb @> ?', $field, $attribute);
    }
}
