<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Injection;

class CompileJsonContainsKey extends PostgresJsonExpression
{
    protected function compile(string $statement): string
    {
        $path = $this->getPath($statement);
        $attribute = $this->getAttribute($statement);
        $fullPath = $this->getField($statement);
        if (!empty($path)) {
            $fullPath .= '->' . $path;
        }

        if (!\filter_var($attribute, FILTER_VALIDATE_INT)) {
            return \sprintf('coalesce((%s)::jsonb ?? %s, false)', $fullPath, $attribute);
        }

        return \vsprintf('CASE WHEN %s THEN %s ELSE false END', [
            \sprintf('jsonb_typeof((%s)::jsonb) = \'array\'', $fullPath),
            \sprintf('jsonb_array_length((%s)::jsonb) >= %s', $fullPath, $attribute + 1),
        ]);
    }
}
