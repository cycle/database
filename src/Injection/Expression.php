<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Injection;

use Cycle\Database\Driver\CompilerInterface;

/**
 * Expression provides ability to mock part of SQL code responsible for operations involving
 * table and column names. This class will quote and prefix every found table name and column while
 * query compilation.
 *
 * Example: new SQLExpression("table.column = table.column + 1");
 *
 * I potentially should have an interface for such class.
 */
class Expression implements FragmentInterface
{
    /** @var string */
    private $expression;

    /** @var ParameterInterface[] */
    private $parameters = [];

    /**
     * @param string $statement
     * @param mixed  ...$parameters
     */
    public function __construct(string $statement, ...$parameters)
    {
        $this->expression = $statement;

        foreach ($parameters as $parameter) {
            if ($parameter instanceof ParameterInterface) {
                $this->parameters[] = $parameter;
            } else {
                $this->parameters[] = new Parameter($parameter);
            }
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return 'exp:' . $this->expression;
    }

    /**
     * @param array $an_array
     * @return Expression
     */
    public static function __set_state(array $an_array): Expression
    {
        return new self(
            $an_array['expression'] ?? $an_array['statement'],
            ...($an_array['parameters'] ?? [])
        );
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return CompilerInterface::EXPRESSION;
    }

    /**
     * @return array
     */
    public function getTokens(): array
    {
        return [
            'expression' => $this->expression,
            'parameters' => $this->parameters
        ];
    }
}
