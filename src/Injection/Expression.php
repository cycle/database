<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Injection;

use Spiral\Database\Driver\CompilerInterface;

/**
 * Expression provides ability to mock part of SQL code responsible for operations involving
 * table and column names. This class will quote and prefix every found table name and column while
 * query compilation.
 *
 * Example: new SQLExpression("table.column = table.column + 1");
 *
 * I potentially should have an interface for such class.
 */
final class Expression implements FragmentInterface
{
    /** @var string */
    private $expression;

    /**
     * @param string $statement
     */
    public function __construct(string $statement)
    {
        $this->expression = $statement;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return 'exp:' . $this->expression;
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
            'expression' => $this->expression
        ];
    }
}
