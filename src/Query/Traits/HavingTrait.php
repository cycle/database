<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query\Traits;

use Closure;
use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;

trait HavingTrait
{
    /** @var array */
    protected $havingTokens = [];

    /**
     * Simple HAVING condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     * @return self|$this
     *
     * @throws BuilderException
     */
    public function having(...$args): self
    {
        $this->registerToken(
            'AND',
            $args,
            $this->havingTokens,
            $this->havingWrapper()
        );

        return $this;
    }

    /**
     * Simple AND HAVING condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     * @return self|$this
     *
     * @throws BuilderException
     */
    public function andHaving(...$args): self
    {
        $this->registerToken(
            'AND',
            $args,
            $this->havingTokens,
            $this->havingWrapper()
        );

        return $this;
    }

    /**
     * Simple OR HAVING condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     *
     * @return self|$this
     *
     * @throws BuilderException
     */
    public function orHaving(...$args): self
    {
        $this->registerToken(
            'OR',
            $args,
            $this->havingTokens,
            $this->havingWrapper()
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
     * Applied to every potential parameter while having tokens generation.
     *
     * @return Closure
     */
    private function havingWrapper(): Closure
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
