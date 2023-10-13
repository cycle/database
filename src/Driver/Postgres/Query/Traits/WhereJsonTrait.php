<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Query\Traits;

use Cycle\Database\Driver\Postgres\Injection\CompileJson;

/**
 * @internal
 */
trait WhereJsonTrait
{
    /**
     * @param non-empty-string $column
     *
     * @return $this|self
     */
    public function whereJson(string $column, mixed $value): self
    {
        $this->registerToken(
            'AND',
            [new CompileJson($column), $value],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     *
     * @return $this|self
     */
    public function andWhereJson(string $column, mixed $value): self
    {
        $this->registerToken(
            'AND',
            [new CompileJson($column), $value],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     *
     * @return $this|self
     */
    public function orWhereJson(string $column, mixed $value): self
    {
        $this->registerToken(
            'OR',
            [new CompileJson($column), $value],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }
}
