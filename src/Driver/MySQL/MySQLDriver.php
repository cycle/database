<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL;

use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\MySQL\Query\MySQLDeleteQuery;
use Cycle\Database\Driver\MySQL\Query\MySQLSelectQuery;
use Cycle\Database\Driver\MySQL\Query\MySQLUpdateQuery;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\QueryBuilder;

/**
 * Talks to mysql databases.
 */
class MySQLDriver extends Driver
{
    /**
     * Integrity violations mysql files under the generic HY000 instead of class 23: a required
     * column left out of the statement, and a row the CHECK rejected. Postgres reports the same
     * two as 23502 and 23514, and SQL Server as 23000.
     *
     * Listed one by one rather than as a range, because their HY000 neighbours are DDL errors and
     * type coercion failures that are not constraint violations at all.
     */
    private const CONSTRAINT_ERRNOS = [
        1364, // ER_NO_DEFAULT_FOR_FIELD
        3819, // ER_CHECK_CONSTRAINT_VIOLATED
    ];

    /**
     * Connection losses the server reports itself, so they arrive numbered rather than in the
     * CR_* range the client library uses for a socket it lost on its own.
     */
    private const CONNECTION_ERRNOS = [
        4031, // ER_CLIENT_INTERACTION_TIMEOUT — the server closed an idle connection
    ];

    /**
     * @param MySQLDriverConfig $config
     */
    public static function create(DriverConfig $config): static
    {
        return new static(
            $config,
            new MySQLHandler(),
            new MySQLCompiler('``'),
            new QueryBuilder(
                new MySQLSelectQuery(),
                new InsertQuery(),
                new MySQLUpdateQuery(),
                new MySQLDeleteQuery(),
            ),
        );
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getType(): string
    {
        return 'MySQL';
    }

    public function getTransactionLevel(): int
    {
        if (!$this->getPDO()->inTransaction()) {
            $this->transactionLevel = 0;

            return 0;
        }

        return $this->transactionLevel;
    }

    /**
     * @see https://dev.mysql.com/doc/refman/8.4/en/client-error-reference.html
     */
    protected function mapException(\Throwable $exception, string $query): StatementException
    {
        $sqlState = self::getSqlState($exception);

        if ($sqlState !== null) {
            // 08S01 — communication link failure.
            if (\str_starts_with($sqlState, '08')) {
                return new StatementException\ConnectionException($exception, $query);
            }

            if (\str_starts_with($sqlState, '23')) {
                return new StatementException\ConstrainException($exception, $query);
            }
        }

        $errno = self::getErrno($exception);

        if (\in_array($errno, self::CONSTRAINT_ERRNOS, true)) {
            return new StatementException\ConstrainException($exception, $query);
        }

        // 2000-2100 is the CR_* range the client library raises when it loses the socket itself,
        // and it never overlaps the server's own error numbers.
        if (($errno > 2000 && $errno < 2100) || \in_array($errno, self::CONNECTION_ERRNOS, true)) {
            return new StatementException\ConnectionException($exception, $query);
        }

        // Last resort, and only for a failure the server did not number itself. HY000 is not
        // enough of a filter here the way it is for the other drivers: mysql files plenty of its
        // own errors under that state, and their text carries table and constraint names — a
        // constraint called `connections` would otherwise be read as a dropped socket.
        if (!self::isServerErrno($errno)) {
            $message = \strtolower($exception->getMessage());

            if (
                \str_contains($message, 'server has gone away')
                || \str_contains($message, 'broken pipe')
                || \str_contains($message, 'connection')
                || \str_contains($message, 'packets out of order')
                || \str_contains($message, 'disconnected by the server because of inactivity')
            ) {
                return new StatementException\ConnectionException($exception, $query);
            }
        }

        return new StatementException($exception, $query);
    }

    /**
     * The mysql error number, which sits next to the SQLSTATE in `errorInfo` and moves into
     * `getCode()` only when the failure predates any statement — a connect attempt that never
     * reached a server has no SQLSTATE to put there instead.
     */
    private static function getErrno(\Throwable $exception): int
    {
        $errorInfo = $exception instanceof \PDOException ? $exception->errorInfo : null;

        return (int) (\is_array($errorInfo) ? $errorInfo[1] ?? $exception->getCode() : $exception->getCode());
    }

    /**
     * Whether the number came from the server rather than the client library. The server numbers
     * its errors from 1000 upwards but leaves 2000-2999 to the client, which is what makes the
     * two tellable apart at all.
     */
    private static function isServerErrno(int $errno): bool
    {
        return $errno >= 1000 && ($errno < 2000 || $errno >= 3000);
    }
}
