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
 * Default implementation of SQLFragmentInterface, provides ability to inject custom SQL code into
 * query builders. Usually used to mock database specific functions.
 *
 * Example: ...->where('time_created', '>', new SQLFragment("NOW()"));
 */
final class Fragment implements FragmentInterface
{
    /** @var string */
    private $statement = null;

    /**
     * @param string $statement
     */
    public function __construct(string $statement)
    {
        $this->statement = $statement;
    }

    /**
     * @param array $an_array
     *
     * @return Fragment
     */
    public static function __set_state(array $an_array): Fragment
    {
        return new static($an_array['statement']);
    }

    /**
     * @param QueryBindings     $bindings
     * @param CompilerInterface $compiler
     * @return string
     */
    public function compile(QueryBindings $bindings, CompilerInterface $compiler): string
    {
        return $this->statement;
    }
}
