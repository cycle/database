<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Injection;

use Spiral\Database\Driver\CompilerInterface;

/**
 * SQLExpression provides ability to mock part of SQL code responsible for operations involving
 * table and column names. This class will quote and prefix every found table name and column while
 * query compilation.
 *
 * Example: new SQLExpression("table.column = table.column + 1");
 *
 * I potentially should have an interface for such class.
 */
class Expression extends Fragment implements ExpressionInterface
{
    /**
     * Unescaped expression.
     *
     * @return string
     */
    public function getExpression(): string
    {
        return $this->statement;
    }

    /**
     * {@inheritdoc}
     */
    public function sqlStatement(CompilerInterface $compiler = null): string
    {
        if (empty($compiler)) {
            //We might need to throw an exception here in some cases
            return $this->statement;
        }

        return $compiler->quote(parent::sqlStatement());
    }
}