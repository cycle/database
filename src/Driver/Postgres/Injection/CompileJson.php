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
    /**
     * @param non-empty-string $statement
     *
     * @return non-empty-string
     */
    protected function compile(string $statement): string
    {
        $wrappedPath = $this->getWrappedPath($statement);
        $attribute = \array_pop($wrappedPath);

        if (!empty($wrappedPath)) {
            return $this->getField($statement) . '->' . \implode('->', $wrappedPath) . '->>' . $attribute;
        }

        return $this->getField($statement) . '->>' . $attribute;
    }
}