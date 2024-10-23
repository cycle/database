<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\QueryParameters;

interface CompilerInterface
{
    // indicates the fragment type to be handled by query compiler
    public const FRAGMENT = 1;
    public const EXPRESSION = 2;
    public const INSERT_QUERY = 4;
    public const SELECT_QUERY = 5;
    public const UPDATE_QUERY = 6;
    public const DELETE_QUERY = 7;
    public const JSON_EXPRESSION = 8;
    public const TOKEN_AND = '@AND';
    public const TOKEN_OR = '@OR';
    public const TOKEN_AND_NOT = '@AND NOT';
    public const TOKEN_OR_NOT = '@OR NOT';

    public function quoteIdentifier(string $identifier): string;

    /**
     * Compile the query fragment.
     *
     */
    public function compile(
        QueryParameters $params,
        string $prefix,
        FragmentInterface $fragment,
    ): string;
}
