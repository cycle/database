<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL\Query\Traits;

use Cycle\Database\Driver\MySQL\Injection\CompileJson;

/**
 * @internal
 */
trait WhereJsonTrait
{
    /**
     * @param mixed ...$args [(column, value), (column, operator, value)]
     *
     * @return $this|self
     */
    public function whereJson(mixed ...$args): self
    {
        $this->registerToken(
            'AND',
            \array_merge([new CompileJson(\array_shift($args))], $args),
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param mixed ...$args [(column, value), (column, operator, value)]
     *
     * @return $this|self
     */
    public function andWhereJson(mixed ...$args): self
    {
        $this->registerToken(
            'AND',
            \array_merge([new CompileJson(\array_shift($args))], $args),
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param mixed ...$args [(column, value), (column, operator, value)]
     *
     * @return $this|self
     */
    public function orWhereJson(mixed ...$args): self
    {
        $this->registerToken(
            'OR',
            \array_merge([new CompileJson(\array_shift($args))], $args),
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }
}
