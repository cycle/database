<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Config\PDOConnectionConfig;
use Cycle\Database\Config\ProvidesSourceString;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Exception\ReadonlyConnectionException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Injection\ParameterInterface;
use Cycle\Database\NamedInterface;
use Cycle\Database\Query\BuilderInterface;
use Cycle\Database\Query\Interpolator;
use Cycle\Database\StatementInterface;
use PDO;
use PDOStatement;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Provides low level abstraction at top of
 */
abstract class Driver implements DriverInterface, NamedInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * DateTime format to be used to perform automatic conversion of DateTime objects.
     *
     * @var non-empty-string (Typehint required for overriding behaviour)
     */
    protected const DATETIME = 'Y-m-d H:i:s';

    protected const DATETIME_MICROSECONDS = 'Y-m-d H:i:s.u';

    protected ?\PDO $pdo = null;
    protected int $transactionLevel = 0;
    protected HandlerInterface $schemaHandler;
    protected BuilderInterface $queryBuilder;

    /** @var \PDOStatement[]|PDOStatementInterface[] */
    protected array $queryCache = [];

    private ?string $name = null;
    private bool $useCache = true;

    protected function __construct(
        protected DriverConfig $config,
        HandlerInterface $schemaHandler,
        protected CompilerInterface $queryCompiler,
        BuilderInterface $queryBuilder,
    ) {
        $this->useCache = $this->config->queryCache;

        $this->schemaHandler = $schemaHandler->withDriver($this);
        $this->queryBuilder = $queryBuilder->withDriver($this);

        if ($this->useCache && $queryCompiler instanceof CachingCompilerInterface) {
            $this->queryCompiler = new CompilerCache($queryCompiler);
        }

        if ($this->config->readonlySchema) {
            $this->schemaHandler = new ReadonlyHandler($this->schemaHandler);
        }
    }

    /**
     * @param non-empty-string $name
     *
     * @internal
     */
    public function withName(string $name): static
    {
        $driver = clone $this;
        $driver->name = $name;

        return $driver;
    }

    public function getName(): string
    {
        return $this->name ?? throw new \RuntimeException('Driver name is not defined.');
    }

    public function isReadonly(): bool
    {
        return $this->config->readonly;
    }

    public function withoutCache(): static
    {
        if ($this->useCache === false) {
            // Cache already disabled
            return $this;
        }

        $driver = clone $this;
        $driver->useCache = false;

        return $driver;
    }

    /**
     * Get driver source database or file name.
     *
     * @psalm-return non-empty-string
     *
     * @throws DriverException
     */
    public function getSource(): string
    {
        $config = $this->config->connection;

        return $config instanceof ProvidesSourceString ? $config->getSourceString() : '*';
    }

    public function getTimezone(): \DateTimeZone
    {
        return new \DateTimeZone($this->config->timezone);
    }

    public function getSchemaHandler(): HandlerInterface
    {
        // do not allow to carry prepared statements between schema changes
        $this->queryCache = [];

        return $this->schemaHandler;
    }

    public function getQueryCompiler(): CompilerInterface
    {
        return $this->queryCompiler;
    }

    public function getQueryBuilder(): BuilderInterface
    {
        return $this->queryBuilder;
    }

    /**
     * Force driver connection.
     *
     * @throws DriverException
     */
    public function connect(): void
    {
        $this->pdo ??= $this->createPDO();
    }

    /**
     * Check if driver already connected.
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * Disconnect driver.
     */
    public function disconnect(): void
    {
        try {
            $this->queryCache = [];
            $this->pdo = null;
        } catch (\Throwable $e) {
            // disconnect error
            $this->logger?->error($e->getMessage());
        }

        $this->transactionLevel = 0;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function quote(mixed $value, int $type = \PDO::PARAM_STR): string
    {
        /** @since PHP 8.1 */
        if ($value instanceof \BackedEnum) {
            $value = (string) $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            $value = $this->formatDatetime($value);
        }

        return $this->getPDO()->quote($value, $type);
    }

    /**
     * Execute query and return query statement.
     *
     * @psalm-param non-empty-string $statement
     *
     * @throws StatementException
     */
    public function query(string $statement, array $parameters = []): StatementInterface
    {
        return $this->statement($statement, $parameters);
    }

    /**
     * Execute query and return number of affected rows.
     *
     * @psalm-param non-empty-string $query
     *
     * @throws StatementException
     * @throws ReadonlyConnectionException
     */
    public function execute(string $query, array $parameters = []): int
    {
        if ($this->isReadonly()) {
            throw ReadonlyConnectionException::onWriteStatementExecution();
        }

        return $this->statement($query, $parameters)->rowCount();
    }

    /**
     * Get id of last inserted row, this method must be called after insert query. Attention,
     * such functionality may not work in some DBMS property (Postgres).
     *
     * @param string|null $sequence Name of the sequence object from which the ID should be returned.
     *
     * @return mixed
     */
    public function lastInsertID(?string $sequence = null)
    {
        $result = $this->getPDO()->lastInsertId();
        $this->logger?->debug("Insert ID: {$result}");

        return $result;
    }

    public function getTransactionLevel(): int
    {
        return $this->transactionLevel;
    }

    /**
     * Start SQL transaction with specified isolation level (not all DBMS support it). Nested
     * transactions are processed using savepoints.
     *
     * @link http://en.wikipedia.org/wiki/Database_transaction
     * @link http://en.wikipedia.org/wiki/Isolation_(database_systems)
     *
     */
    public function beginTransaction(?string $isolationLevel = null): bool
    {
        ++$this->transactionLevel;

        if ($this->transactionLevel === 1) {
            if ($isolationLevel !== null) {
                $this->setIsolationLevel($isolationLevel);
            }

            $this->logger?->info('Begin transaction');

            try {
                return $this->getPDO()->beginTransaction();
            } catch (\Throwable  $e) {
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
     * Commit the active database transaction.
     *
     * @throws StatementException
     */
    public function commitTransaction(): bool
    {
        // Check active transaction
        if (!$this->getPDO()->inTransaction()) {
            $this->logger?->warning(
                \sprintf(
                    'Attempt to commit a transaction that has not yet begun. Transaction level: %d',
                    $this->transactionLevel,
                ),
            );

            if ($this->transactionLevel === 0) {
                return false;
            }

            $this->transactionLevel = 0;
            return true;
        }

        --$this->transactionLevel;

        if ($this->transactionLevel === 0) {
            $this->logger?->info('Commit transaction');

            try {
                return $this->getPDO()->commit();
            } catch (\Throwable $e) {
                throw $this->mapException($e, 'COMMIT TRANSACTION');
            }
        }

        $this->releaseSavepoint($this->transactionLevel + 1);

        return true;
    }

    /**
     * Rollback the active database transaction.
     *
     * @throws StatementException
     */
    public function rollbackTransaction(): bool
    {
        // Check active transaction
        if (!$this->getPDO()->inTransaction()) {
            $this->logger?->warning(
                \sprintf(
                    'Attempt to rollback a transaction that has not yet begun. Transaction level: %d',
                    $this->transactionLevel,
                ),
            );

            $this->transactionLevel = 0;
            return false;
        }

        --$this->transactionLevel;

        if ($this->transactionLevel === 0) {
            $this->logger?->info('Rollback transaction');

            try {
                return $this->getPDO()->rollBack();
            } catch (\Throwable  $e) {
                throw $this->mapException($e, 'ROLLBACK TRANSACTION');
            }
        }

        $this->rollbackSavepoint($this->transactionLevel + 1);

        return true;
    }

    /**
     * @psalm-param non-empty-string $identifier
     */
    public function identifier(string $identifier): string
    {
        return $this->queryCompiler->quoteIdentifier($identifier);
    }

    public function __debugInfo(): array
    {
        return [
            'connection' => $this->config->connection,
            'source' => $this->getSource(),
            'connected' => $this->isConnected(),
            'options' => $this->config,
        ];
    }

    /**
     * Compatibility with deprecated methods.
     *
     * @psalm-param non-empty-string $name
     *
     * @deprecated this method will be removed in a future releases.
     */
    public function __call(string $name, array $arguments): mixed
    {
        return match ($name) {
            'isProfiling' => true,
            'setProfiling' => null,
            'getSchema' => $this->getSchemaHandler()->getSchema(
                $arguments[0],
                $arguments[1] ?? null,
            ),
            'tableNames' => $this->getSchemaHandler()->getTableNames(),
            'hasTable' => $this->getSchemaHandler()->hasTable($arguments[0]),
            'identifier' => $this->getQueryCompiler()->quoteIdentifier($arguments[0]),
            'eraseData' => $this->getSchemaHandler()->eraseTable(
                $this->getSchemaHandler()->getSchema($arguments[0]),
            ),
            'insertQuery',
            'selectQuery',
            'updateQuery',
            'deleteQuery' => \call_user_func_array(
                [$this->queryBuilder, $name],
                $arguments,
            ),
            default => throw new DriverException("Undefined driver method `{$name}`"),
        };
    }

    public function __clone()
    {
        $this->schemaHandler = $this->schemaHandler->withDriver($this);
        $this->queryBuilder = $this->queryBuilder->withDriver($this);
    }

    /**
     * Disconnect and destruct.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Create instance of PDOStatement using provided SQL query and set of parameters and execute
     * it. Will attempt singular reconnect.
     *
     * @psalm-param non-empty-string $query
     *
     * @throws StatementException
     */
    protected function statement(string $query, iterable $parameters = [], bool $retry = true): StatementInterface
    {
        $queryStart = \microtime(true);

        try {
            $statement = $this->bindParameters($this->prepare($query), $parameters);
            $statement->execute();

            return new Statement($statement);
        } catch (\Throwable $e) {
            $e = $this->mapException($e, Interpolator::interpolate($query, $parameters));

            if (
                $retry
                && $this->transactionLevel === 0
                && $e instanceof StatementException\ConnectionException
            ) {
                $this->disconnect();

                return $this->statement($query, $parameters, false);
            }

            throw $e;
        } finally {
            if ($this->logger !== null) {
                $queryString = $this->config->options['logInterpolatedQueries']
                    ? Interpolator::interpolate($query, $parameters, $this->config->options)
                    : $query;

                $contextParameters = $this->config->options['logQueryParameters']
                    ? $parameters
                    : [];

                $context = $this->defineLoggerContext(
                    $queryStart,
                    $statement ?? null,
                    $contextParameters,
                );

                if (isset($e)) {
                    $this->logger->error($queryString, $context);
                    $this->logger->alert($e->getMessage());
                } else {
                    $this->logger->info($queryString, $context);
                }
            }
        }
    }

    /**
     * @psalm-param non-empty-string $query
     */
    protected function prepare(string $query): \PDOStatement|PDOStatementInterface
    {
        if ($this->useCache && isset($this->queryCache[$query])) {
            return $this->queryCache[$query];
        }

        $statement = $this->getPDO()->prepare($query);
        if ($this->useCache) {
            $this->queryCache[$query] = $statement;
        }

        return $statement;
    }

    /**
     * Bind parameters into statement.
     */
    protected function bindParameters(
        \PDOStatement|PDOStatementInterface $statement,
        iterable $parameters,
    ): \PDOStatement|PDOStatementInterface {
        $index = 0;
        foreach ($parameters as $name => $parameter) {
            if (\is_string($name)) {
                $index = $name;
            } else {
                $index++;
            }

            $type = \PDO::PARAM_STR;

            if ($parameter instanceof ParameterInterface) {
                $type = $parameter->getType();
                $parameter = $parameter->getValue();
            }

            /** @since PHP 8.1 */
            if ($parameter instanceof \BackedEnum) {
                $type = \PDO::PARAM_STR;
                $parameter = $parameter->value;
            }

            if ($parameter instanceof \DateTimeInterface) {
                $parameter = $this->formatDatetime($parameter);
            }

            // numeric, @see http://php.net/manual/en/pdostatement.bindparam.php
            $statement->bindValue($index, $parameter, $type);
        }

        return $statement;
    }

    /**
     * Convert DateTime object into local database representation. Driver will automatically force
     * needed timezone.
     *
     * @throws DriverException
     */
    protected function formatDatetime(\DateTimeInterface $value): string
    {
        try {
            $datetime = match (true) {
                $value instanceof \DateTimeImmutable => $value->setTimezone($this->getTimezone()),
                $value instanceof \DateTime => \DateTimeImmutable::createFromMutable($value)
                    ->setTimezone($this->getTimezone()),
                default => (new \DateTimeImmutable('now', $this->getTimezone()))->setTimestamp($value->getTimestamp()),
            };
        } catch (\Throwable $e) {
            throw new DriverException($e->getMessage(), (int) $e->getCode(), $e);
        }

        return $datetime->format(
            $this->config->options['withDatetimeMicroseconds'] ? self::DATETIME_MICROSECONDS : self::DATETIME,
        );
    }

    /**
     * Convert PDO exception into query or integrity exception.
     *
     * @psalm-param non-empty-string $query
     */
    abstract protected function mapException(
        \Throwable $exception,
        string $query,
    ): StatementException;

    /**
     * Set transaction isolation level, this feature may not be supported by specific database
     * driver.
     *
     * @psalm-param non-empty-string $level
     */
    protected function setIsolationLevel(string $level): void
    {
        $this->logger?->info("Transaction isolation level '{$level}'");
        $this->execute("SET TRANSACTION ISOLATION LEVEL {$level}");
    }

    /**
     * Create nested transaction save point.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param int $level Savepoint name/id, must not contain spaces and be valid database identifier.
     */
    protected function createSavepoint(int $level): void
    {
        $this->logger?->info("Transaction: new savepoint 'SVP{$level}'");

        $this->execute('SAVEPOINT ' . $this->identifier("SVP{$level}"));
    }

    /**
     * Commit/release savepoint.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param int $level Savepoint name/id, must not contain spaces and be valid database identifier.
     */
    protected function releaseSavepoint(int $level): void
    {
        $this->logger?->info("Transaction: release savepoint 'SVP{$level}'");

        $this->execute('RELEASE SAVEPOINT ' . $this->identifier("SVP{$level}"));
    }

    /**
     * Rollback savepoint.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param int $level Savepoint name/id, must not contain spaces and be valid database identifier.
     */
    protected function rollbackSavepoint(int $level): void
    {
        $this->logger?->info("Transaction: rollback savepoint 'SVP{$level}'");

        $this->execute('ROLLBACK TO SAVEPOINT ' . $this->identifier("SVP{$level}"));
    }

    /**
     * Create instance of configured PDO class.
     */
    protected function createPDO(): \PDO|PDOInterface
    {
        $connection = $this->config->connection;

        if (!$connection instanceof PDOConnectionConfig) {
            throw new \InvalidArgumentException(
                'Could not establish PDO connection using non-PDO configuration',
            );
        }

        return new \PDO(
            dsn: $connection->getDsn(),
            username: $connection->getUsername(),
            password: $connection->getPassword(),
            options: $connection->getOptions(),
        );
    }

    /**
     * Get associated PDO connection. Must automatically connect if such connection does not exists.
     *
     * @throws DriverException
     */
    protected function getPDO(): \PDO|PDOInterface
    {
        if ($this->pdo === null) {
            $this->connect();
        }

        return $this->pdo;
    }

    /**
     * Creating a context for logging
     *
     * @param float $queryStart Query start time
     * @param \PDOStatement|PDOStatementInterface|null $statement Statement object
     * @param iterable $parameters Query parameters
     *
     */
    protected function defineLoggerContext(float $queryStart, \PDOStatement|PDOStatementInterface|null $statement, iterable $parameters = []): array
    {
        $context = [
            'driver' => $this->getType(),
            'elapsed' => \microtime(true) - $queryStart,
        ];
        if ($statement !== null) {
            $context['rowCount'] = $statement->rowCount();
        }

        foreach ($parameters as $parameter) {
            $context['parameters'][] = Interpolator::resolveValue($parameter, $this->config->options);
        }

        return $context;
    }
}
