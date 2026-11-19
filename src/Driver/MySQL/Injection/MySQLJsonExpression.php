<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL\Injection;

use Cycle\Database\Injection\JsonExpression;

abstract class MySQLJsonExpression extends JsonExpression
{
    protected function getQuotes(): string
    {
        return '``';
    }

    /**
     * Unless NO_BACKSLASH_ESCAPES is set, MySQL reads a backslash in a quoted literal as an escape, so a value
     * with one goes as a hex literal, which no sql_mode reinterprets. That includes every key with `"` or a
     * control character, which the JSON path escapes with a backslash. Not a bound parameter: MySQL matches
     * generated-column and functional indexes only against a constant path.
     */
    protected function quoteStringLiteral(string $value, string $quote = "'"): string
    {
        return \str_contains($value, '\\')
            ? "_utf8mb4 X'" . \bin2hex($value) . "'"
            : parent::quoteStringLiteral($value, $quote);
    }
}
