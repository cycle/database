<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use PDO;
use PDOStatement;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Cycle\Database\Exception\ConfigException;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Exception\ReadonlyConnectionException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Injection\ParameterInterface;
use Cycle\Database\Query\BuilderInterface;
use Cycle\Database\Query\Interpolator;
use Cycle\Database\StatementInterface;
use Throwable;
use Spiral\Database\Query\BuilderInterface as SpiralBuilderInterface;
use Spiral\Database\Driver\HandlerInterface as SpiralHandlerInterface;
use Spiral\Database\Driver\CompilerInterface as SpiralCompilerInterface;

/**
 * Provides low level abstraction at top of
 */
abstract class Driver implements DriverInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    // DateTime format to be used to perform automatic conversion of DateTime objects.
    protected const DATETIME = 'Y-m-d H:i:s';

    // Driver specific PDO options
    protected const DEFAULT_PDO_OPTIONS = [
        PDO::ATTR_CASE             => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * Connection configuration described in DBAL config file. Any driver can be used as data source
     * for multiple databases as table prefix and quotation defined on Database instance level.
     *
     * @var array
     */
    protected $options = [
        // allow reconnects
        'reconnect'      => true,

        // all datetime objects will be converted relative to
        // this timezone (must match with DB timezone!)
        'timezone'       => 'UTC',

        // DSN
        'connection'     => '',
        'username'       => '',
        'password'       => '',

        // pdo options
        'options'        => [],

        // enables query caching
        'queryCache'     => true,

        // disable schema modifications
        'readonlySchema' => false,

        // disable write expressions
        'readonly'       => false,
    ];

    /** @var PDO|null */
    protected $pdo;

    /** @var int */
    protected $transactionLevel = 0;

    /** @var HandlerInterface */
    protected $schemaHandler;

    /** @var CompilerInterface */
    protected $queryCompiler;

    /** @var BuilderInterface */
    protected $queryBuilder;

    /** @var PDOStatement[] */
    protected $queryCache = [];

    /**
     * @param array             $options
     * @param SpiralHandlerInterface|HandlerInterface $schemaHandler The signature of
     *        this argument will be changed to {@see HandlerInterface} in future release.
     * @param SpiralCompilerInterface|CompilerInterface $queryCompiler The signature of
     *        this argument will be changed to {@see CompilerInterface} in future release.
     * @param SpiralBuilderInterface|BuilderInterface $queryBuilder The signature of
     *        this argument will be changed to {@see BuilderInterface} in future release.
     */
    public function __construct(
        array $options,
        SpiralHandlerInterface $schemaHandler,
        SpiralCompilerInterface $queryCompiler,
        SpiralBuilderInterface $queryBuilder
    ) {
        $this->schemaHandler = $schemaHandler->withDriver($this);
        $this->queryBuilder = $queryBuilder->withDriver($this);
        $this->queryCompiler = $queryCompiler;

        $options['options'] = array_replace(
            static::DEFAULT_PDO_OPTIONS,
            $options['options'] ?? []
        );

        $this->options = array_replace(
            $this->options,
            $options
        );

        if ($this->options['queryCache'] && $queryCompiler instanceof CachingCompilerInterface) {
            $this->queryCompiler = new CompilerCache($queryCompiler);
        }

        if ($this->options['readonlySchema']) {
            $this->schemaHandler = new ReadonlyHandler($this->schemaHandler);
        }

        // Actualize DSN
        $this->updateDSN();
    }

    /**
     * Updates an internal options
     *
     * @return void
     */
    private function updateDSN(): void
    {
        [$connection, $this->options['username'], $this->options['password']] = $this->parseDSN();

        // Update connection. The DSN field can be located in one of the
        // following keys of the configuration array.
        switch (true) {
            case \array_key_exists('dsn', $this->options):
                $this->options['dsn'] = $connection;
                break;
            case \array_key_exists('addr', $this->options):
                $this->options['addr'] = $connection;
                break;
            default:
                $this->options['connection'] = $connection;
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isReadonly(): bool
    {
        return (bool)($this->options['readonly'] ?? false);
    }

    /**
     * Disconnect and destruct.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'addr'      => $this->getDSN(),
            'source'    => $this->getSource(),
            'connected' => $this->isConnected(),
            'options'   => $this->options['options'],
        ];
    }

    /**
     * Compatibility with deprecated methods.
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     *
     * @deprecated this method will be removed in a future releases.
     */
    public function __call(string $name, array $arguments)
    {
        switch ($name) {
            case 'isProfiling':
                return true;
            case 'setProfiling':
                return null;
            case 'getSchema':
                return $this->getSchemaHandler()->getSchema(
                    $arguments[0],
                    $arguments[1] ?? null
                );
            case 'tableNames':
                return $this->getSchemaHandler()->getTableNames();
            case 'hasTable':
                return $this->getSchemaHandler()->hasTable($arguments[0]);
            case 'identifier':
                return $this->getQueryCompiler()->quoteIdentifier($arguments[0]);
            case 'eraseData':
                return $this->getSchemaHandler()->eraseTable(
                    $this->getSchemaHandler()->getSchema($arguments[0])
                );

            case 'insertQuery':
            case 'selectQuery':
            case 'updateQuery':
            case 'deleteQuery':
                return call_user_func_array(
                    [$this->queryBuilder, $name],
                    $arguments
                );
        }

        throw new DriverException("Undefined driver method `{$name}`");
    }

    /**
     * Get driver source database or file name.
     *
     * @return string
     *
     * @throws DriverException
     */
    public function getSource(): string
    {
        if (preg_match('/(?:dbname|database)=([^;]+)/i', $this->getDSN(), $matches)) {
            return $matches[1];
        }

        return '*';
    }

    /**
     * @inheritDoc
     */
    public function getTimezone(): DateTimeZone
    {
        return new DateTimeZone($this->options['timezone']);
    }

    /**
     * @inheritdoc
     */
    public function getSchemaHandler(): HandlerInterface
    {
        // do not allow to carry prepared statements between schema changes
        $this->queryCache = [];

        return $this->schemaHandler;
    }

    /**
     * @inheritdoc
     */
    public function getQueryCompiler(): CompilerInterface
    {
        return $this->queryCompiler;
    }

    /**
     * @return BuilderInterface
     */
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
        if ($this->pdo === null) {
            $this->pdo = $this->createPDO();
        }
    }

    /**
     * Check if driver already connected.
     *
     * @return bool
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
        } catch (Throwable $e) {
            // disconnect error
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }

        $this->transactionLevel = 0;
    }

    /**
     * @inheritdoc
     */
    public function quote($value, int $type = PDO::PARAM_STR): string
    {
        if ($value instanceof DateTimeInterface) {
            $value = $this->formatDatetime($value);
        }

        return $this->getPDO()->quote($value, $type);
    }

    /**
     * Execute query and return query statement.
     *
     * @param string $statement
     * @param array  $parameters
     * @return StatementInterface
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
     * @param string $query
     * @param array  $parameters
     * @return int
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
     * @return mixed
     */
    public function lastInsertID(string $sequence = null)
    {
        $result = $this->getPDO()->lastInsertId();
        if ($this->logger !== null) {
            $this->logger->debug("Insert ID: {$result}");
        }

        return $result;
    }

    /**
     * Start SQL transaction with specified isolation level (not all DBMS support it). Nested
     * transactions are processed using savepoints.
     *
     * @link http://en.wikipedia.org/wiki/Database_transaction
     * @link http://en.wikipedia.org/wiki/Isolation_(database_systems)
     *
     * @param string $isolationLevel
     * @return bool
     */
    public function beginTransaction(string $isolationLevel = null): bool
    {
        ++$this->transactionLevel;

        if ($this->transactionLevel === 1) {
            if ($isolationLevel !== null) {
                $this->setIsolationLevel($isolationLevel);
            }

            if ($this->logger !== null) {
                $this->logger->info('Begin transaction');
            }

            try {
                return $this->getPDO()->beginTransaction();
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
     * Commit the active database transaction.
     *
     * @return bool
     *
     * @throws StatementException
     */
    public function commitTransaction(): bool
    {
        // Check active transaction
        if (!$this->getPDO()->inTransaction()) {
            if ($this->logger !== null) {
                $this->logger->warning(
                    sprintf(
                        'Attempt to commit a transaction that has not yet begun. Transaction level: %d',
                        $this->transactionLevel
                    )
                );
            }

            if ($this->transactionLevel === 0) {
                return false;
            }

            $this->transactionLevel = 0;
            return true;
        }

        --$this->transactionLevel;

        if ($this->transactionLevel === 0) {
            if ($this->logger !== null) {
                $this->logger->info('Commit transaction');
            }

            try {
                return $this->getPDO()->commit();
            } catch (Throwable $e) {
                throw $this->mapException($e, 'COMMIT TRANSACTION');
            }
        }

        $this->releaseSavepoint($this->transactionLevel + 1);

        return true;
    }

    /**
     * Rollback the active database transaction.
     *
     * @return bool
     *
     * @throws StatementException
     */
    public function rollbackTransaction(): bool
    {
        // Check active transaction
        if (!$this->getPDO()->inTransaction()) {
            if ($this->logger !== null) {
                $this->logger->warning(
                    sprintf(
                        'Attempt to rollback a transaction that has not yet begun. Transaction level: %d',
                        $this->transactionLevel
                    )
                );
            }

            $this->transactionLevel = 0;
            return false;
        }

        --$this->transactionLevel;

        if ($this->transactionLevel === 0) {
            if ($this->logger !== null) {
                $this->logger->info('Rollback transaction');
            }

            try {
                return $this->getPDO()->rollBack();
            } catch (Throwable  $e) {
                throw $this->mapException($e, 'ROLLBACK TRANSACTION');
            }
        }

        $this->rollbackSavepoint($this->transactionLevel + 1);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function identifier(string $identifier): string
    {
        return $this->queryCompiler->quoteIdentifier($identifier);
    }

    /**
     * Create instance of PDOStatement using provided SQL query and set of parameters and execute
     * it. Will attempt singular reconnect.
     *
     * @param string    $query
     * @param iterable  $parameters
     * @param bool|null $retry
     * @return StatementInterface
     *
     * @throws StatementException
     */
    protected function statement(
        string $query,
        iterable $parameters = [],
        bool $retry = true
    ): StatementInterface {
        $queryStart = microtime(true);

        try {
            $statement = $this->bindParameters($this->prepare($query), $parameters);
            $statement->execute();

            return new Statement($statement);
        } catch (Throwable  $e) {
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
                $queryString = Interpolator::interpolate($query, $parameters);
                $context = $this->defineLoggerContext($queryStart, $statement ?? null);

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
     * @param string $query
     * @return PDOStatement
     */
    protected function prepare(string $query): PDOStatement
    {
        if ($this->options['queryCache'] && isset($this->queryCache[$query])) {
            return $this->queryCache[$query];
        }

        $statement = $this->getPDO()->prepare($query);
        if ($this->options['queryCache']) {
            $this->queryCache[$query] = $statement;
        }

        return $statement;
    }

    /**
     * Bind parameters into statement.
     *
     * @param PDOStatement $statement
     * @param iterable     $parameters
     * @return PDOStatement
     */
    protected function bindParameters(PDOStatement $statement, iterable $parameters): PDOStatement
    {
        $index = 0;
        foreach ($parameters as $name => $parameter) {
            if (is_string($name)) {
                $index = $name;
            } else {
                $index++;
            }

            $type = PDO::PARAM_STR;

            if ($parameter instanceof ParameterInterface) {
                $type = $parameter->getType();
                $parameter = $parameter->getValue();
            }

            if ($parameter instanceof DateTimeInterface) {
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
     * @param DateTimeInterface $value
     * @return string
     *
     * @throws DriverException
     */
    protected function formatDatetime(DateTimeInterface $value): string
    {
        try {
            $datetime = new DateTimeImmutable('now', $this->getTimezone());
        } catch (Throwable $e) {
            throw new DriverException($e->getMessage(), $e->getCode(), $e);
        }

        return $datetime->setTimestamp($value->getTimestamp())->format(static::DATETIME);
    }

    /**
     * Convert PDO exception into query or integrity exception.
     *
     * @param Throwable $exception
     * @param string    $query
     * @return StatementException
     */
    abstract protected function mapException(
        Throwable $exception,
        string $query
    ): StatementException;

    /**
     * Set transaction isolation level, this feature may not be supported by specific database
     * driver.
     *
     * @param string $level
     */
    protected function setIsolationLevel(string $level): void
    {
        if ($this->logger !== null) {
            $this->logger->info("Transaction isolation level '{$level}'");
        }

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
        if ($this->logger !== null) {
            $this->logger->info("Transaction: new savepoint 'SVP{$level}'");
        }

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
        if ($this->logger !== null) {
            $this->logger->info("Transaction: release savepoint 'SVP{$level}'");
        }

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
        if ($this->logger !== null) {
            $this->logger->info("Transaction: rollback savepoint 'SVP{$level}'");
        }

        $this->execute('ROLLBACK TO SAVEPOINT ' . $this->identifier("SVP{$level}"));
    }

    /**
     * @return array{string, string, string}
     */
    private function parseDSN(): array
    {
        $dsn = $this->getDSN();

        $user = (string)($this->options['username'] ?? '');
        $pass = (string)($this->options['password'] ?? '');

        if (\strpos($dsn, '://') > 0) {
            $parts = \parse_url($dsn);

            if (!isset($parts['scheme'])) {
                throw new ConfigException('Configuration database scheme must be defined');
            }

            // Update username and password from DSN if not defined.
            $user = $user ?: $parts['user'] ?? '';
            $pass = $pass ?: $parts['pass'] ?? '';

            // Build new DSN
            $dsn = \sprintf('%s:host=%s', $parts['scheme'], $parts['host'] ?? 'localhost');

            if (isset($parts['port'])) {
                $dsn .= ';port=' . $parts['port'];
            }

            if (isset($parts['path']) && \trim($parts['path'], '/')) {
                $dsn .= ';dbname=' . \trim($parts['path'], '/');
            }
        }

        return [$dsn, $user, $pass];
    }

    /**
     * Create instance of configured PDO class.
     *
     * @return PDO
     */
    protected function createPDO(): PDO
    {
        [$dsn, $user, $pass] = $this->parseDSN();

        return new PDO($dsn, $user, $pass, $this->options['options']);
    }

    /**
     * Get associated PDO connection. Must automatically connect if such connection does not exists.
     *
     * @return PDO
     *
     * @throws DriverException
     */
    protected function getPDO(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }

        return $this->pdo;
    }

    /**
     * Connection DSN.
     *
     * @return string
     */
    protected function getDSN(): string
    {
        return $this->options['connection'] ?? $this->options['dsn'] ?? $this->options['addr'];
    }

    /**
     * Creating a context for logging
     *
     * @param float             $queryStart Query start time
     * @param PDOStatement|null $statement  Statement
     *
     * @return array
     */
    protected function defineLoggerContext(float $queryStart, ?PDOStatement $statement): array
    {
        $context = [
            'elapsed' => microtime(true) - $queryStart,
        ];
        if ($statement !== null) {
            $context['rowCount'] = $statement->rowCount();
        }

        return $context;
    }
}
