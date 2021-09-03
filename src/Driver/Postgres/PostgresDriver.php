<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres;

use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\Postgres\Query\PostgresInsertQuery;
use Cycle\Database\Driver\Postgres\Query\PostgresSelectQuery;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Query\QueryBuilder;
use Cycle\Database\Query\UpdateQuery;
use Throwable;

/**
 * Talks to postgres databases.
 */
class PostgresDriver extends Driver
{
    /**
     * Option key for default postgres schema name.
     *
     * @var non-empty-string
     */
    private const OPT_DEFAULT_SCHEMA = 'default_schema';

    /**
     * Option key for all available postgres schema names.
     *
     * @var non-empty-string
     */
    private const OPT_AVAILABLE_SCHEMAS = 'schema';

    /**
     * Default public schema name for all postgres connections.
     *
     * @var non-empty-string
     */
    public const PUBLIC_SCHEMA = 'public';

    /**
     * Cached list of primary keys associated with their table names. Used by InsertBuilder to
     * emulate last insert id.
     *
     * @var array
     */
    private array $primaryKeys = [];

    /**
     * Schemas to search tables in (search_path)
     *
     * @var string[]
     * @psalm-var non-empty-array<non-empty-string>
     */
    private array $searchPath = [];

    /**
     * Schemas to search tables in
     *
     * @var string[]
     * @psalm-var non-empty-array<non-empty-string>
     */
    private array $searchSchemas = [];

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        // default query builder
        parent::__construct(
            $options,
            new PostgresHandler(),
            new PostgresCompiler('""'),
            new QueryBuilder(
                new PostgresSelectQuery(),
                new PostgresInsertQuery(),
                new UpdateQuery(),
                new DeleteQuery()
            )
        );

        $this->defineSchemas($this->options);
    }

    /**
     * @return string
     */
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
     * @return bool
     */
    public function shouldUseDefinedSchemas(): bool
    {
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
     * @return string|null
     */
    public function getPrimaryKey(string $prefix, string $table): ?string
    {
        $name = $prefix . $table;
        if (isset($this->primaryKeys[$name])) {
            return $this->primaryKeys[$name];
        }

        if (!$this->getSchemaHandler()->hasTable($name)) {
            throw new DriverException(
                "Unable to fetch table primary key, no such table '{$name}' exists"
            );
        }

        $this->primaryKeys[$name] = $this->getSchemaHandler()
                                         ->getSchema($table, $prefix)
                                         ->getPrimaryKeys();

        if (count($this->primaryKeys[$name]) === 1) {
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
     * @param string|null $isolationLevel
     *
     * @return bool
     */
    public function beginTransaction(string $isolationLevel = null): bool
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
            } catch (Throwable  $e) {
                $e = $this->mapException($e, 'BEGIN TRANSACTION');

                if (
                    $e instanceof StatementException\ConnectionException
                    && $this->options['reconnect']
                ) {
                    $this->disconnect();

                    try {
                        return $this->getPDO()->beginTransaction();
                    } catch (Throwable $e) {
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
     * @param  string  $name
     *
     * @return string[]
     */
    public function parseSchemaAndTable(string $name): array
    {
        $schema = null;
        $table = $name;

        if (str_contains($name, '.')) {
            [$schema, $table] = explode('.', $name, 2);

            if ($schema === '$user') {
                $schema = $this->options['username'];
            }
        }

        return [$schema ?? $this->searchSchemas[0], $table];
    }

    /**
     * {@inheritdoc}
     */
    protected function createPDO(): \PDO
    {
        // spiral is purely UTF-8
        $pdo = parent::createPDO();
        $pdo->exec("SET NAMES 'UTF-8'");

        if ($this->searchPath !== []) {
            $schema = '"' . implode('", "', $this->searchPath) . '"';
            $pdo->exec("SET search_path TO {$schema}");
            $this->searchPath = [];
        }

        return $pdo;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapException(Throwable $exception, string $query): StatementException
    {
        $message = strtolower($exception->getMessage());

        if (
            strpos($message, 'eof detected') !== false
            || strpos($message, 'broken pipe') !== false
            || strpos($message, '0800') !== false
            || strpos($message, '080P') !== false
            || strpos($message, 'connection') !== false
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
    private function defineSchemas(array $options): void
    {
        $options[self::OPT_AVAILABLE_SCHEMAS] = (array)($options[self::OPT_AVAILABLE_SCHEMAS] ?? []);

        $defaultSchema = $options[self::OPT_DEFAULT_SCHEMA]
            ?? $options[self::OPT_AVAILABLE_SCHEMAS][0]
            ?? static::PUBLIC_SCHEMA;

        $this->searchSchemas = $this->searchPath = array_values(array_unique(
            [$defaultSchema, ...$options[self::OPT_AVAILABLE_SCHEMAS]]
        ));

        if (($pos = array_search('$user', $this->searchSchemas, true)) !== false) {
            $this->searchSchemas[$pos] = $options['username'];
        }
    }
}
