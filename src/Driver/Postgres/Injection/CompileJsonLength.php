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
     * @param int<0, max> $length
     * @param non-empty-string $operator
     */
    public function __construct(
        string $statement,
        int $length,
        protected string $operator,
    ) {
        parent::__construct($statement, $length);
    }

    protected function compile(string $statement): string
    {
        $path = $this->getPath($statement);
        $attribute = $this->getAttribute($statement);
        $field = $this->getField($statement);

        $fullPath = !empty($path)
            ? \sprintf('%s->%s->%s', $field, $path, $attribute)
            : \sprintf('%s->%s', $field, $attribute);

        return \sprintf('jsonb_array_length((%s)::jsonb) %s ?', $fullPath, $this->operator);
    }
}
