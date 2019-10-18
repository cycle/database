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
use Spiral\Database\Driver\QueryBindings;

/**
 * SQLExpression provides ability to mock part of SQL code responsible for operations involving
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
    private $expression = null;

    /**
     * @param string $statement
     */
    public function __construct(string $statement)
    {
        $this->expression = $statement;
    }

    /**
     * Unescaped expression.
     *
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(
        QueryBindings $bindings,
        CompilerInterface $compiler
    ): string {
        if (empty($compiler)) {
            //We might need to throw an exception here in some cases
            return $this->expression;
        }

        return $compiler->quote($bindings, $this->expression);
    }
}
