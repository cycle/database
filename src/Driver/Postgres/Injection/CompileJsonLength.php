<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Injection;

class CompileJsonLength extends PostgresJsonExpression
{
    /**
     * @param non-empty-string $statement
     * @param positive-int|0 $length
     * @param non-empty-string $operator
     */
    public function __construct(
        string $statement,
        int $length,
        protected string $operator
    ) {
        parent::__construct($statement, $length);
    }

    /**
     * @param non-empty-string $statement
     *
     * @return non-empty-string
     */
    protected function compile(string $statement): string
    {
        $wrappedPath = $this->getWrappedPath($statement);
        $attribute = \array_pop($wrappedPath);
        $field = $this->getField($statement);

        $column = !empty($wrappedPath)
            ? $field . '->' . \implode('->', $wrappedPath) . '->' . $attribute
            : $field . '->' . $attribute;

        return \sprintf('jsonb_array_length((%s)::jsonb) %s ?', $column, $this->operator);
    }
}
