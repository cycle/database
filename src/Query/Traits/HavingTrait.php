<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query\Traits;

use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;

trait HavingTrait
{
    /**
     * Set of generated having tokens, format must be supported by QueryCompilers.
     *
     * @see AbstractWhere
     *
     * @var array
     */
    protected $havingTokens = [];

    /**
     * Simple HAVING condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     * @return self|$this
     *
     * @throws BuilderException
     * @see AbstractWhere
     *
     */
    public function having(...$args): self
    {
        $this->createToken('AND', $args, $this->havingTokens, $this->havingWrapper());

        return $this;
    }

    /**
     * Simple AND HAVING condition with various set of arguments.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     * @return self|$this
     *
     * @throws BuilderException
     * @see AbstractWhere
     *
     */
    public function andHaving(...$args): self
    {
        $this->createToken('AND', $args, $this->havingTokens, $this->havingWrapper());

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
     * @see AbstractWhere
     *
     */
    public function orHaving(...$args): self
    {
        $this->createToken('OR', $args, $this->havingTokens, $this->havingWrapper());

        return $this;
    }

    /**
     * Convert various amount of where function arguments into valid where token.
     *
     * @param string   $joiner     Boolean joiner (AND | OR).
     * @param array    $parameters Set of parameters collected from where functions.
     * @param array    $tokens     Array to aggregate compiled tokens. Reference.
     * @param callable $wrapper    Callback or closure used to wrap/collect every potential
     *                             parameter.
     *
     * @throws BuilderException
     * @see AbstractWhere
     *
     */
    abstract protected function createToken($joiner, array $parameters, &$tokens, callable $wrapper);

    /**
     * Applied to every potential parameter while having tokens generation.
     *
     * @return \Closure
     */
    private function havingWrapper(): \Closure
    {
        return static function ($parameter) {
            if ($parameter instanceof FragmentInterface) {
                return $parameter;
            }

            if (is_array($parameter)) {
                throw new BuilderException('Arrays must be wrapped with Parameter instance');
            }

            //Wrapping all values with ParameterInterface
            if (!$parameter instanceof ParameterInterface) {
                $parameter = new Parameter($parameter, Parameter::DETECT_TYPE);
            };

            return $parameter;
        };
    }
}
