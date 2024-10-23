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
     *
     *
     * @see https://dev.mysql.com/doc/refman/5.6/en/error-messages-client.html#error_cr_conn_host_error
     */
    protected function mapException(\Throwable $exception, string $query): StatementException
    {
        if ((int) $exception->getCode() === 23000) {
            return new StatementException\ConstrainException($exception, $query);
        }

        $message = \strtolower($exception->getMessage());

        if (
            \str_contains($message, 'server has gone away')
            || \str_contains($message, 'broken pipe')
            || \str_contains($message, 'connection')
            || \str_contains($message, 'packets out of order')
            || ((int) $exception->getCode() > 2000 && (int) $exception->getCode() < 2100)
        ) {
            return new StatementException\ConnectionException($exception, $query);
        }

        return new StatementException($exception, $query);
    }
}
