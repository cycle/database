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
     * Integrity violations mysql files under HY000 instead of class 23, where Postgres reports
     * them as 23502 and 23514 and SQL Server as 23000. Listed one by one rather than as a range:
     * their HY000 neighbours are DDL errors and type coercion failures.
     */
    private const CONSTRAINT_ERRNOS = [
        1364, // ER_NO_DEFAULT_FOR_FIELD
        3819, // ER_CHECK_CONSTRAINT_VIOLATED
    ];

    /**
     * Losses of an established connection, named one by one because their neighbours in the CR_*
     * range are client misuse — CR_COMMANDS_OUT_OF_SYNC, CR_PARAMS_NOT_BOUND — that a reconnect
     * cannot fix and a retry would only repeat.
     */
    private const CONNECTION_ERRNOS = [
        2006, // CR_SERVER_GONE_ERROR
        2013, // CR_SERVER_LOST
        2055, // CR_SERVER_LOST_EXTENDED
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

        // The whole CR_* range means a lost socket only before a statement exists, which is exactly
        // when getCode() carries the number instead of a SQLSTATE. Widening this to errorInfo would
        // pull in the client-misuse errnos the constant above names.
        $code = (int) $exception->getCode();

        if (($code > 2000 && $code < 2100) || \in_array($errno, self::CONNECTION_ERRNOS, true)) {
            return new StatementException\ConnectionException($exception, $query);
        }

        // Last resort, and only for a failure neither the server numbered nor PDO classified.
        // Unlike the other drivers, mysql files plenty of its own errors under HY000, and their
        // text carries table and constraint names, so the state alone is not a usable gate here.
        if (!self::isServerErrno($errno) && self::isGenericSqlState($sqlState)) {
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
     * its errors from 1000 upwards but leaves 2000-2999 to the client.
     */
    private static function isServerErrno(int $errno): bool
    {
        return $errno >= 1000 && ($errno < 2000 || $errno >= 3000);
    }
}
