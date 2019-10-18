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
 * Declares ability to be converted into sql statement.
 */
interface FragmentInterface
{
    /**
     * @param QueryBindings     $bindings
     * @param CompilerInterface $compiler
     * @return string
     */
    public function compile(
        QueryBindings $bindings,
        CompilerInterface $compiler
    ): string;
}
