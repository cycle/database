<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Query\Traits;

use Closure;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\FragmentInterface as Fragment;
use Cycle\Database\Injection\ParameterInterface as Parameter;

trait WhereTrait
{
    protected array $whereTokens = [];

    /**
     * Simple WHERE condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     *
     * @return $this|self
     * @throws BuilderException
     *
     */
    public function where(mixed ...$args): self
    {
        $this->registerToken(
            'AND',
            $args,
            $this->whereTokens,
            $this->whereWrapper(),
        );

        return $this;
    }

    /**
     * Simple AND WHERE condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     *
     * @return $this|self
     * @throws BuilderException
     *
     */
    public function andWhere(mixed ...$args): self
    {
        $this->registerToken(
            'AND',
            $args,
            $this->whereTokens,
            $this->whereWrapper(),
        );

        return $this;
    }

    /**
     * Simple OR WHERE condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     *
     * @return $this|self
     * @throws BuilderException
     *
     */
    public function orWhere(mixed ...$args): self
    {
        $this->registerToken(
            'OR',
            $args,
            $this->whereTokens,
            $this->whereWrapper(),
        );

        return $this;
    }

    /**
     * Simple WHERE NOT condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     *
     * @return $this|self
     * @throws BuilderException
     *
     */
    public function whereNot(mixed ...$args): self
    {
        $this->registerToken(
            'AND NOT',
            $args,
            $this->whereTokens,
            $this->whereWrapper(),
        );

        return $this;
    }

    /**
     * Simple AND WHERE NOT condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     *
     * @return $this|self
     * @throws BuilderException
     *
     */
    public function andWhereNot(mixed ...$args): self
    {
        $this->registerToken(
            'AND NOT',
            $args,
            $this->whereTokens,
            $this->whereWrapper(),
        );

        return $this;
    }

    /**
     * Simple OR WHERE NOT condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     *
     * @return $this|self
     * @throws BuilderException
     *
     */
    public function orWhereNot(mixed ...$args): self
    {
        $this->registerToken(
            'OR NOT',
            $args,
            $this->whereTokens,
            $this->whereWrapper(),
        );

        return $this;
    }

    /**
     * Convert various amount of where function arguments into valid where token.
     *
     * @psalm-param non-empty-string $boolean Boolean joiner (AND | OR).
     *
     * @param array $params Set of parameters collected from where functions.
     * @param array $tokens Array to aggregate compiled tokens. Reference.
     * @param callable $wrapper Callback or closure used to wrap/collect every potential parameter.
     *
     * @throws BuilderException
     */
    abstract protected function registerToken(
        string $boolean,
        array $params,
        array &$tokens,
        callable $wrapper,
    ): void;

    /**
     * Applied to every potential parameter while where tokens generation. Used to prepare and
     * collect where parameters.
     */
    protected function whereWrapper(): \Closure
    {
        return static fn($parameter) => $parameter instanceof Parameter || $parameter instanceof Fragment
            ? $parameter
            : new \Cycle\Database\Injection\Parameter($parameter);
    }
}
