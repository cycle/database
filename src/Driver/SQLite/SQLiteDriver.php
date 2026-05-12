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
use Cycle\Database\Driver\CursorableInterface;
use Cycle\Database\Driver\CursorOptions;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\SQLite\Query\SQLiteDeleteQuery;
use Cycle\Database\Driver\SQLite\Query\SQLiteSelectQuery;
use Cycle\Database\Driver\SQLite\Query\SQLiteUpdateQuery;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\QueryBuilder;
use Cycle\Database\StatementInterface;

class SQLiteDriver extends Driver implements CursorableInterface
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

    /**
     * Iterate rows from a SELECT lazily.
     *
     * SQLite has no SQL-level DECLARE CURSOR, but its core engine is already
     * row-oriented: every `PDOStatement::fetch()` advances the prepared
     * statement by exactly one row via `sqlite3_step()`, pulling it from disk
     * without buffering the full result set. The {@see CursorableInterface}
     * contract (snapshot consistency for the duration of the enclosing
     * transaction) is therefore satisfied by the engine + an active
     * transaction:
     *
     *  - WAL mode: a reader snapshot is taken on the first read and kept
     *    until COMMIT / ROLLBACK; concurrent writers do not affect it.
     *  - Rollback journal mode: a SHARED lock is held that blocks writers
     *    until the transaction completes.
     *
     * SQLite has no driver-specific cursor options; the base {@see CursorOptions}
     * (`mode` only) is sufficient.
     *
     * @return \Generator<int, array<array-key, mixed>>
     *
     * @throws DriverException When no transaction is active.
     */
    public function cursor(
        string $statement,
        iterable $parameters = [],
        CursorOptions $options = new CursorOptions(),
        int $mode = StatementInterface::FETCH_ASSOC,
    ): \Generator {
        if ($this->getTransactionLevel() === 0) {
            throw new DriverException(
                'SQLite cursor requires an active transaction to guarantee snapshot consistency. '
                . 'Wrap the cursor iteration in Database::transaction() or call beginTransaction() before cursor().',
            );
        }

        $sqliteStatement = $this->statement($statement, $parameters);

        try {
            while (($row = $sqliteStatement->fetch($mode)) !== false) {
                yield $row;
            }
        } finally {
            $sqliteStatement->close();
        }
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
