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
 * Expressions require instance of QueryCompiler at moment of statementGeneration. For
 * simplification purposes every expression is instance of fragment (no compiler is required),
 * however such instance has to be provided at moment of compilation.
 */
interface ExpressionInterface extends FragmentInterface
{
    /**
     * @param CompilerInterface|null $compiler
     * @return string
     */
    public function sqlStatement(CompilerInterface $compiler = null): string;
}
