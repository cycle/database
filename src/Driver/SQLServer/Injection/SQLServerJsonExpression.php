<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer\Injection;

use Cycle\Database\Injection\JsonExpression;

abstract class SQLServerJsonExpression extends JsonExpression
{
    /**
     * @return non-empty-string
     */
    protected function getQuotes(): string
    {
        return '[]';
    }

    /**
     * @param non-empty-string $statement
     *
     * @return non-empty-string
     */
    protected function getField(string $statement): string
    {
        $parts = \explode('->', $statement, 2);

        return $this->quoter->quote($parts[0]);
    }

    /**
     * @param non-empty-string $statement
     *
     * @return non-empty-string
     */
    protected function getPath(string $statement): string
    {
        $parts = \explode('->', $statement, 2);

        return \count($parts) > 1 ? ', ' . $this->wrapPath($parts[1]) : '';
    }
}