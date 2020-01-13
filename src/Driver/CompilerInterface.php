<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver;

use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Query\QueryParameters;

interface CompilerInterface
{
    // indicates the fragment type to be handled by query compiler
    public const FRAGMENT     = 1;
    public const EXPRESSION   = 2;
    public const INSERT_QUERY = 4;
    public const SELECT_QUERY = 5;
    public const UPDATE_QUERY = 6;
    public const DELETE_QUERY = 7;

    public const TOKEN_AND = '@AND';
    public const TOKEN_OR  = '@OR';

    /**
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier(string $identifier): string;

    /**
     * Compile the query fragment.
     *
     * @param QueryParameters   $params
     * @param string            $prefix
     * @param FragmentInterface $fragment
     * @return string
     */
    public function compile(
        QueryParameters $params,
        string $prefix,
        FragmentInterface $fragment
    ): string;
}
