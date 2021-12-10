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
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Injection\ParameterInterface;

trait WhereTrait
{
    protected array $whereTokens = [];

    /**
     * Simple WHERE condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     * @return self|$this
     *
     * @throws BuilderException
     */
    public function where(mixed ...$args): self
    {
        $this->registerToken(
            'AND',
            $args,
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * Simple AND WHERE condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     * @return self|$this
     *
     * @throws BuilderException
     */
    public function andWhere(mixed ...$args): self
    {
        $this->registerToken(
            'AND',
            $args,
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * Simple OR WHERE condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     * @return self|$this
     *
     * @throws BuilderException
     */
    public function orWhere(mixed ...$args): self
    {
        $this->registerToken(
            'OR',
            $args,
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * Convert various amount of where function arguments into valid where token.
     *
     * @param string $boolean    Boolean joiner (AND | OR).
     * @param array    $params     Set of parameters collected from where functions.
     * @param array $tokens     Array to aggregate compiled tokens. Reference.
     * @param callable $wrapper    Callback or closure used to wrap/collect every potential
     *                             parameter.
     *
     * @throws BuilderException
     */
    abstract protected function registerToken(
        string $boolean,
        array $params,
        array &$tokens,
        callable $wrapper
    ): void;

    /**
     * Applied to every potential parameter while where tokens generation. Used to prepare and
     * collect where parameters.
     */
    private function whereWrapper(): Closure
    {
        return static function ($parameter) {
            \is_array($parameter) && throw new BuilderException('Arrays must be wrapped with Parameter instance');

            return !$parameter instanceof ParameterInterface && !$parameter instanceof FragmentInterface
                ? new Parameter($parameter)
                : $parameter;
        };
    }
}
