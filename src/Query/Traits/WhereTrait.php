<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
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
    /** @var array */
    protected $whereTokens = [];

    /**
     * Simple WHERE condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     *
     * @throws BuilderException
     *
     * @return $this|self
     */
    public function where(...$args): self
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
     *
     * @throws BuilderException
     *
     * @return $this|self
     */
    public function andWhere(...$args): self
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
     *
     * @throws BuilderException
     *
     * @return $this|self
     */
    public function orWhere(...$args): self
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
     * @param string   $boolean    Boolean joiner (AND | OR).
     * @param array    $params     Set of parameters collected from where functions.
     * @param array    $tokens     Array to aggregate compiled tokens. Reference.
     * @param callable $wrapper    Callback or closure used to wrap/collect every potential
     *                             parameter.
     *
     * @throws BuilderException
     */
    abstract protected function registerToken(
        $boolean,
        array $params,
        &$tokens,
        callable $wrapper
    );

    /**
     * Applied to every potential parameter while where tokens generation. Used to prepare and
     * collect where parameters.
     *
     * @return Closure
     */
    private function whereWrapper(): Closure
    {
        return static function ($parameter) {
            if (is_array($parameter)) {
                throw new BuilderException(
                    'Arrays must be wrapped with Parameter instance'
                );
            }

            if (!$parameter instanceof ParameterInterface && !$parameter instanceof FragmentInterface) {
                return new Parameter($parameter);
            }

            return $parameter;
        };
    }
}
