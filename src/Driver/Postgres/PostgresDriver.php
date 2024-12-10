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
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\QueryBuilder;

/**
 * Talks to postgres databases.
 */
class PostgresDriver extends Driver
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
        $message = \strtolower($exception->getMessage());

        if (
            \str_contains($message, 'eof detected')
            || \str_contains($message, 'broken pipe')
            || \str_contains($message, '0800')
            || \str_contains($message, '080p')
            || \str_contains($message, 'connection')
        ) {
            return new StatementException\ConnectionException($exception, $query);
        }

        if ((int) $exception->getCode() >= 23000 && (int) $exception->getCode() < 24000) {
            return new StatementException\ConstrainException($exception, $query);
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
