<?php

/**
 * This file is part of database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Exception;

class ReadonlyConnectionException extends DBALException
{
    private const WRITE_STMT_MESSAGE = 'Can not execute non-query statement on readonly connection.';

    /**
     *
     * @return static
     */
    public static function onWriteStatementExecution(int $code = 0, ?\Throwable $prev = null): self
    {
        return new self(self::WRITE_STMT_MESSAGE, $code, $prev);
    }
}
