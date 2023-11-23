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
    protected function compile(string $statement): string
    {
        $path = $this->getPath($statement);
        $field = $this->getField($statement);
        $attribute = $this->findAttribute($statement);

        if (empty($attribute)) {
            return \sprintf('(%s)::jsonb @> ?', $field);
        }

        if (!empty($path)) {
            return \sprintf('(%s->%s->%s)::jsonb @> ?', $field, $path, $attribute);
        }

        return \sprintf('(%s->%s)::jsonb @> ?', $field, $attribute);
    }
}
