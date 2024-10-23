<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLite;

use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\SQLite\Query\SQLiteDeleteQuery;
use Cycle\Database\Driver\SQLite\Query\SQLiteSelectQuery;
use Cycle\Database\Driver\SQLite\Query\SQLiteUpdateQuery;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\QueryBuilder;

class SQLiteDriver extends Driver
{
    /**
     * @param SQLiteDriverConfig $config
     */
    public static function create(DriverConfig $config): static
    {
        return new static(
            $config,
            new SQLiteHandler(),
            new SQLiteCompiler('""'),
            new QueryBuilder(
                new SQLiteSelectQuery(),
                new InsertQuery(),
                new SQLiteUpdateQuery(),
                new SQLiteDeleteQuery(),
            ),
        );
    }

    public function getType(): string
    {
        return 'SQLite';
    }

    protected function mapException(\Throwable $exception, string $query): StatementException
    {
        if ((int) $exception->getCode() === 23000) {
            return new StatementException\ConstrainException($exception, $query);
        }

        return new StatementException($exception, $query);
    }

    protected function setIsolationLevel(string $level): void
    {
        $this->logger?->alert("Transaction isolation level is not fully supported by SQLite ({$level})");
    }
}
