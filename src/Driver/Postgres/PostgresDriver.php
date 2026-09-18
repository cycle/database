<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres;

use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\PDOInterface;
use Cycle\Database\Driver\Postgres\Query\PostgresDeleteQuery;
use Cycle\Database\Driver\Postgres\Query\PostgresInsertQuery;
use Cycle\Database\Driver\Postgres\Query\PostgresSelectQuery;
use Cycle\Database\Driver\Postgres\Query\PostgresUpdateQuery;
use Cycle\Database\Driver\CursorInterface;
use Cycle\Database\Driver\CursorOptions;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\QueryBuilder;
use Cycle\Database\StatementInterface;

/**
 * Talks to postgres databases.
 */
class PostgresDriver extends Driver implements CursorInterface
{
    /**
     * Cached list of primary keys associated with their table names. Used by InsertBuilder to
     * emulate last insert id.
     *
     */
    private array $primaryKeys = [];

    /**
     * Schemas to search tables in (search_path)
     *
     * @var string[]
     *
     * @psalm-var non-empty-array<non-empty-string>
     */
    private array $searchPath = [];

    /**
     * Schemas to search tables in
     *
     * @var string[]
     *
     * @psalm-var non-empty-array<string>
     */
    private array $searchSchemas = [];

    /**
     * @param PostgresDriverConfig $config
     */
    public static function create(DriverConfig $config): static
    {
        $driver = new static(
            $config,
            new PostgresHandler(),
            new PostgresCompiler('""'),
            new QueryBuilder(
                new PostgresSelectQuery(),
                new PostgresInsertQuery(),
                new PostgresUpdateQuery(),
                new PostgresDeleteQuery(),
            ),
        );

        $driver->defineSchemas();

        return $driver;
    }

    public function getType(): string
    {
        return 'Postgres';
    }

    /**
     * Schemas to search tables in
     *
     * @return string[]
     */
    public function getSearchSchemas(): array
    {
        return $this->searchSchemas;
    }

    /**
     * Check if schemas are defined
     *
     */
    public function shouldUseDefinedSchemas(): bool
    {
        // TODO May be redundant?
        //      Search schemas list can not be empty.
        return $this->searchSchemas !== [];
    }

    /**
     * Get singular primary key associated with desired table. Used to emulate last insert id.
     *
     * @param string $prefix Database prefix if any.
     * @param string $table  Fully specified table name, including postfix.
     *
     * @throws DriverException
     *
     */
    public function getPrimaryKey(string $prefix, string $table): ?string
    {
        $name = $prefix . $table;
        if (\array_key_exists($name, $this->primaryKeys)) {
            return $this->primaryKeys[$name];
        }

        if (!$this->getSchemaHandler()->hasTable($name)) {
            throw new DriverException(
                "Unable to fetch table primary key, no such table '{$name}' exists",
            );
        }

        $this->primaryKeys[$name] = $this->getSchemaHandler()
            ->getSchema($table, $prefix)
            ->getPrimaryKeys();

        if (\count($this->primaryKeys[$name]) === 1) {
            //We do support only single primary key
            $this->primaryKeys[$name] = $this->primaryKeys[$name][0];
        } else {
            $this->primaryKeys[$name] = null;
        }

        return $this->primaryKeys[$name];
    }

    /**
     * Reset primary keys cache.
     */
    public function resetPrimaryKeys(): void
    {
        $this->primaryKeys = [];
    }

    /**
     * Start SQL transaction with specified isolation level (not all DBMS support it). Nested
     * transactions are processed using savepoints.
     *
     * @link http://en.wikipedia.org/wiki/Database_transaction
     * @link http://en.wikipedia.org/wiki/Isolation_(database_systems)
     *
     *
     */
    public function beginTransaction(?string $isolationLevel = null): bool
    {
        ++$this->transactionLevel;

        if ($this->transactionLevel === 1) {
            $this->logger?->info('Begin transaction');

            try {
                $ok = $this->getPDO()->beginTransaction();
                if ($isolationLevel !== null) {
                    $this->setIsolationLevel($isolationLevel);
                }

                return $ok;
            } catch (\Throwable $e) {
                $e = $this->mapException($e, 'BEGIN TRANSACTION');

                if (
                    $e instanceof StatementException\ConnectionException
                    && $this->config->reconnect
                ) {
                    $this->disconnect();

                    try {
                        $this->transactionLevel = 1;
                        return $this->getPDO()->beginTransaction();
                    } catch (\Throwable $e) {
                        $this->transactionLevel = 0;
                        throw $this->mapException($e, 'BEGIN TRANSACTION');
                    }
                } else {
                    $this->transactionLevel = 0;
                    throw $e;
                }
            }
        }

        $this->createSavepoint($this->transactionLevel);

        return true;
    }

    /**
     * Open a Postgres server-side cursor for the given SELECT and yield rows
     * lazily. Provides snapshot consistency within the enclosing transaction.
     *
     * Requires an active transaction (cursor lifetime is bound to it unless
     * {@see PostgresCursorOptions::$withHold} is set, in which case the cursor
     * survives `COMMIT` and the result is materialized on the server). The
     * cursor is declared with NO SCROLL — only forward fetches are performed.
     * The cursor is closed when the generator is fully consumed or
     * garbage-collected.
     *
     * @return \Generator<int, array<array-key, mixed>>
     *
     * @throws DriverException
     */
    #[\Override]
    public function cursor(
        string $statement,
        iterable $parameters = [],
        CursorOptions $options = new PostgresCursorOptions(),
        int $mode = StatementInterface::FETCH_ASSOC,
    ): \Generator {
        $opts = PostgresCursorOptions::from($options);

        if ($opts->chunkSize < 1) {
            throw new DriverException('Chunk size must be a positive integer.');
        }

        if ($this->getTransactionLevel() === 0) {
            throw new DriverException(
                'Postgres server-side cursor requires an active transaction. '
                . 'Wrap the cursor iteration in Database::transaction() or call beginTransaction() before cursor().',
            );
        }

        $cursorName = '"' . ($opts->name ?? 'c_' . \bin2hex(\random_bytes(8))) . '"';
        $holdClause = $opts->withHold ? ' WITH HOLD' : '';
        $declareSql = "DECLARE {$cursorName} NO SCROLL CURSOR{$holdClause} FOR {$statement}";
        $fetchSql = "FETCH FORWARD {$opts->chunkSize} FROM {$cursorName}";
        $closeSql = "CLOSE {$cursorName}";

        try {
            $this->statement($declareSql, $parameters);

            do {
                $chunkStatement = $this->statement($fetchSql);
                $rows = $chunkStatement->fetchAll($mode);
                $chunkStatement->close();

                foreach ($rows as $row) {
                    yield $row;
                }
            } while (\count($rows) === $opts->chunkSize);
        } finally {
            try {
                $this->statement($closeSql);
            } catch (\Throwable) {
                // Cursor may already be gone (e.g. transaction was rolled back) — swallow.
            }

            // Avoid polluting the prepared-statement cache with single-use SQL strings.
            unset(
                $this->queryCache[$declareSql],
                $this->queryCache[$fetchSql],
                $this->queryCache[$closeSql],
            );
        }
    }

    /**
     * Parse the table name and extract the schema and table.
     *
     * @return string[]
     */
    public function parseSchemaAndTable(string $name): array
    {
        $schema = null;
        $table = $name;

        if (\str_contains($name, '.')) {
            [$schema, $table] = \explode('.', $name, 2);

            if ($schema === '$user') {
                $schema = $this->config->connection->getUsername();
            }
        }

        return [$schema ?? $this->searchSchemas[0], $table];
    }

    protected function createPDO(): \PDO|PDOInterface
    {
        // Cycle is purely UTF-8
        $pdo = parent::createPDO();
        // TODO Should be moved into driver settings.
        $pdo->exec("SET NAMES 'UTF-8'");

        $schema = '"' . \implode('", "', $this->searchPath) . '"';
        $pdo->exec("SET search_path TO {$schema}");

        return $pdo;
    }

    protected function mapException(\Throwable $exception, string $query): StatementException
    {
        $sqlState = self::getSqlState($exception);

        if ($sqlState !== null) {
            // The 57 states are listed rather than taken as a class: 57014 `query_canceled` is a
            // statement timeout that leaves the session usable.
            if (
                \str_starts_with($sqlState, '08')
                || \in_array($sqlState, ['53300', '57P01', '57P02', '57P03', '57P04', '57P05'], true)
            ) {
                return new StatementException\ConnectionException($exception, $query);
            }

            // Compared as a string: `23P01` (exclusion violation) is not a number, and a numeric
            // cast truncates it to 23.
            if (\str_starts_with($sqlState, '23')) {
                return new StatementException\ConstrainException($exception, $query);
            }
        }

        // A socket the server or a pooler dropped arrives as HY000, with the reason only in the
        // text. A state the server did classify never reaches these needles: Postgres prints the
        // offending row in DETAIL, and a uuid or an email there matches them.
        if (self::isGenericSqlState($sqlState)) {
            $message = \strtolower($exception->getMessage());

            if (
                \str_contains($message, 'eof detected')
                || \str_contains($message, 'broken pipe')
                || \str_contains($message, 'connection')
            ) {
                return new StatementException\ConnectionException($exception, $query);
            }
        }

        return new StatementException($exception, $query);
    }

    /**
     * Define schemas from config
     */
    private function defineSchemas(): void
    {
        /** @var PostgresDriverConfig $config */
        $config = $this->config;

        $this->searchSchemas = $this->searchPath = \array_values($config->schema);

        $position = \array_search('$user', $this->searchSchemas, true);
        if ($position !== false) {
            $this->searchSchemas[$position] = (string) $config->connection->getUsername();
        }
    }
}
