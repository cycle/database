<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Exception\CompilerException;

use Cycle\Database\Exception\CompilerException;

class UnexpectedOperatorException extends CompilerException
{
    /**
     * Exception for the value sequence (IN or NOT IN operator).
     *
     * @param string $operator User-provided operator.
     */
    public static function sequence(string $operator): self
    {
        return new self(
            \sprintf(
                'Unable to compile query, unexpected operator `%s` provided for a value sequence. %s',
                $operator,
                'Allowed operators: `IN`, `NOT IN` (or `=`, `!=` as sugar).',
            ),
        );
    }
}
