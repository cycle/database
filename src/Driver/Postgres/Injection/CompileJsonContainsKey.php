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
    /**
     * @param non-empty-string $statement
     *
     * @return non-empty-string
     */
    protected function compile(string $statement): string
    {
        $wrappedPath = $this->getWrappedPath($statement);
        $attribute = \array_pop($wrappedPath);
        $path = $this->getField($statement);
        if ($wrappedPath !== []) {
            $path .= '->' . \implode('->', $wrappedPath);
        }

        if (!\filter_var($attribute, FILTER_VALIDATE_INT)) {
            return \sprintf('coalesce((%s)::jsonb ?? %s, false)', $path, $attribute);
        }

        return \vsprintf('CASE WHEN %s THEN %s ELSE false END', [
            \sprintf('jsonb_typeof((%s)::jsonb) = \'array\'', $path),
            \sprintf('jsonb_array_length((%s)::jsonb) >= %s', $path, $attribute + 1),
        ]);
    }
}
