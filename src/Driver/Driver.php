<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver;

use PDO;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use Spiral\Database\Driver\Traits\BuilderTrait;
use Spiral\Database\Driver\Traits\ProfilingTrait;
use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Exception\DriverException;
use Spiral\Database\Exception\StatementException;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;
use Spiral\Database\Query\Interpolator;
use Spiral\Database\Schema\AbstractTable;
use Spiral\Database\StatementInterface;

/**
 * Driver abstraction is responsible for DBMS specific set of functions and used by Databases to
 * hide implementation specific functionality. Extends PDODriver and adds ability to create driver
 * specific query builders and schemas (basically operates like a factory).
 */
abstract class Driver implements DriverInterface, LoggerAwareInterface
{
    use ProfilingTrait;
    use BuilderTrait;

    // One of DatabaseInterface types, must be set on implementation.
    protected const TYPE = '@undefined';

    // Driver specific class names.
    protected const TABLE_SCHEMA_CLASS = '';
    protected const COMMANDER          = '';
    protected const QUERY_COMPILER     = '';

    // DateTime format to be used to perform automatic conversion of DateTime objects.
    protected const DATETIME = 'Y-m-d H:i:s';

    // Driver specific PDO options
    protected const DEFAULT_PDO_OPTIONS = [
        PDO::ATTR_CASE             => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    /**
     * Connection configuration described in DBAL config file. Any driver can be used as data source
     * for multiple databases as table prefix and quotation defined on Database instance level.
     *
     * @var array
     */
    protected $options = [
        // allow reconnects
        'reconnect'  => true,

        //All datetime objects will be converted relative to this timezone (must match with DB timezone!)
        'timezone'   => 'UTC',

        //DSN
        'connection' => '',
        'username'   => '',
        'password'   => '',

        // pdo options
        'options'    => [],
    ];

    /** @var PDO|null */
    protected $pdo;

    /** @var TransactionScope */
    private $tScope;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->tScope = new TransactionScope();

        $options['options'] = ($options['options'] ?? []) + static::DEFAULT_PDO_OPTIONS;
        $this->options = $options + $this->options;
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
            'connection' => $this->options['connection'] ?? $this->options['dsn'] ?? $this->options['addr'],
            'connected'  => $this->isConnected(),
            'source'     => $this->getSource(),
            'options'    => $this->options['options'],
        ];
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
        if (preg_match('/(?:dbname|database)=([^;]+)/i', $this->options['connection'], $matches)) {
            return $matches[1];
        }

        throw new DriverException('Unable to locate source name');
    }

    /**
     * Database type driver linked to.
     *
     * @return string
     */
    public function getType(): string
    {
        return static::TYPE;
    }

    /**
     * Connection specific timezone, at this moment locked to UTC.
     *
     * @return \DateTimeZone
     */
    public function getTimezone(): \DateTimeZone
    {
        return new \DateTimeZone($this->options['timezone']);
    }

    /**
     * @inheritdoc
     */
    public function getSchema(string $table, string $prefix = ''): AbstractTable
    {
        $schema = static::TABLE_SCHEMA_CLASS;

        return new $schema($this, $table, $prefix);
    }

    /**
     * @inheritdoc
     */
    public function getCompiler(string $prefix = ''): CompilerInterface
    {
        $compiler = static::QUERY_COMPILER;

        return new $compiler(new Quoter($this, $prefix));
    }

    /**
     * Force driver connection.
     *
     * @throws DriverException
     */
    public function connect(): void
    {
        if (!$this->isConnected()) {
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
            $this->pdo = null;
        } catch (\Throwable $e) {
            // disconnect error
            $this->getLogger()->error($e->getMessage());
        }

        $this->tScope->reset();
    }

    /**
     * @inheritdoc
     */
    public function quote($value, int $type = PDO::PARAM_STR): string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $this->formatDatetime($value);
        }

        return $this->getPDO()->quote($value, $type);
    }

    /**
     * @inheritdoc
     */
    public function identifier(string $identifier): string
    {
        return $identifier === '*' ? '*' : '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * Wraps PDO query method with custom representation class.
     *
     * @param string $statement
     * @param array  $parameters
     * @return StatementInterface
     *
     * @throws StatementException
     */
    public function query(string $statement, array $parameters = []): StatementInterface
    {
        //Forcing specific return class
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
     */
    public function execute(string $query, array $parameters = []): int
    {
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
        $pdo = $this->getPDO();
        $result = $sequence ? (int)$pdo->lastInsertId($sequence) : (int)$pdo->lastInsertId();
        $this->getLogger()->debug("Insert ID: {$result}");

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
     * @param bool   $cacheStatements
     * @return bool
     */
    public function beginTransaction(string $isolationLevel = null, bool $cacheStatements = false): bool
    {
        $this->tScope->open($cacheStatements);

        if ($this->tScope->getLevel() === 1) {
            if ($isolationLevel !== null) {
                $this->setIsolationLevel($isolationLevel);
            }

            $this->getLogger()->info('Begin transaction');

            try {
                return $this->getPDO()->beginTransaction();
            } catch (\Throwable | \PDOException $e) {
                $e = $this->mapException($e, 'BEGIN TRANSACTION');

                if (
                    $e instanceof StatementException\ConnectionException
                    && $this->options['reconnect']
                ) {
                    $this->disconnect();

                    try {
                        return $this->getPDO()->beginTransaction();
                    } catch (\PDOException $e) {
                        throw $this->mapException($e, 'BEGIN TRANSACTION');
                    }
                }
            }
        }

        $this->createSavepoint($this->tScope->getLevel());

        return true;
    }

    /**
     * Commit the active database transaction.
     *
     * @return bool
     */
    public function commitTransaction(): bool
    {
        $this->tScope->close();

        if ($this->tScope->getLevel() === 0) {
            $this->getLogger()->info('Commit transaction');

            try {
                return $this->getPDO()->commit();
            } catch (\Throwable | \PDOException $e) {
                throw $this->mapException($e, 'COMMIT TRANSACTION');
            }
        }

        $this->releaseSavepoint($this->tScope->getLevel() + 1);

        return true;
    }

    /**
     * Rollback the active database transaction.
     *
     * @return bool
     */
    public function rollbackTransaction(): bool
    {
        $this->tScope->close();

        if ($this->tScope->getLevel() === 0) {
            $this->getLogger()->info('Rollback transaction');

            try {
                return $this->getPDO()->rollBack();
            } catch (\Throwable | \PDOException $e) {
                throw $this->mapException($e, 'ROLLBACK TRANSACTION');
            }
        }

        $this->rollbackSavepoint($this->tScope->getLevel() + 1);

        return true;
    }

    /**
     * Bind parameters into statement.
     *
     * @param \PDOStatement        $statement
     * @param ParameterInterface[] $parameters Named hash of ParameterInterface.
     * @return \PDOStatement
     */
    protected function bindParameters(\PDOStatement $statement, iterable $parameters): \PDOStatement
    {
        foreach ($parameters as $index => $parameter) {
            if ($parameter->getValue() instanceof \DateTimeInterface) {
                // original parameter must not be altered
                $parameter = $parameter->withValue(
                    $this->formatDatetime($parameter->getValue())
                );
            }

            // numeric, @see http://php.net/manual/en/pdostatement.bindparam.php
            $statement->bindValue($index, $parameter->getValue(), $parameter->getType());
        }

        return $statement;
    }

    /**
     * Convert PDO exception into query or integrity exception.
     *
     * @param \Throwable $exception
     * @param string     $query
     * @return StatementException
     */
    abstract protected function mapException(\Throwable $exception, string $query): StatementException;

    /**
     * Set transaction isolation level, this feature may not be supported by specific database
     * driver.
     *
     * @param string $level
     */
    protected function setIsolationLevel(string $level): void
    {
        $this->getLogger()->info("Transaction isolation level '{$level}'");
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
        $this->getLogger()->info("Transaction: new savepoint 'SVP{$level}'");
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
        $this->getLogger()->info("Transaction: release savepoint 'SVP{$level}'");
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
        $this->getLogger()->info("Transaction: rollback savepoint 'SVP{$level}'");
        $this->execute('ROLLBACK TO SAVEPOINT ' . $this->identifier("SVP{$level}"));
    }

    /**
     * Create instance of configured PDO class.
     *
     * @return PDO
     */
    protected function createPDO(): PDO
    {
        return new PDO(
            $this->options['connection'] ?? $this->options['dsn'] ?? $this->options['addr'],
            $this->options['username'],
            $this->options['password'],
            $this->options['options']
        );
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
        if (!$this->isConnected()) {
            $this->connect();
        }

        return $this->pdo;
    }

    /**
     * Convert DateTime object into local database representation. Driver will automatically force
     * needed timezone.
     *
     * @param \DateTimeInterface $value
     * @return string
     *
     * @throws DriverException
     */
    protected function formatDatetime(\DateTimeInterface $value): string
    {
        try {
            $datetime = new \DateTimeImmutable('now', $this->getTimezone());
        } catch (\Exception $e) {
            throw new DriverException($e->getMessage(), $e->getCode(), $e);
        }

        return $datetime->setTimestamp($value->getTimestamp())->format(static::DATETIME);
    }

    /**
     * Create instance of PDOStatement using provided SQL query and set of parameters and execute
     * it. Will attempt singular reconnect.
     *
     * @param string    $query
     * @param array     $parameters Parameters to be binded into query.
     * @param bool|null $retry
     * @return StatementInterface
     *
     * @throws StatementException
     */
    private function statement(string $query, array $parameters = [], bool $retry = true): StatementInterface
    {
        if ($retry === null) {
            $retry = $this->options['reconnect'];
        }

        $queryStart = microtime(true);
        $flattened = $this->flattenParameters($parameters);

        try {
            $statement = $this->bindParameters($this->prepare($query), $flattened);
            $statement->execute();

            return new Statement($statement);
        } catch (\Throwable | \PDOException $e) {
            $queryString = Interpolator::interpolate($query, $flattened, false);
            $e = $this->mapException($e, $queryString);

            if (
                $retry
                && $e instanceof StatementException\ConnectionException
                && $this->tScope->getLevel() === 0
            ) {
                $this->disconnect();

                // retrying
                return $this->statement($query, $parameters, false);
            }

            throw $e;
        } finally {
            if (isset($e) || !$this->getLogger() instanceof NullLogger) {
                $context = [
                    'elapsed' => microtime(true) - $queryStart
                ];

                $logger = $this->getLogger();
                $queryString = $queryString ?? Interpolator::interpolate($query, $flattened, false);

                if (isset($e)) {
                    $logger->error($queryString, $context);
                    $logger->alert($e->getMessage());
                } else {
                    $this->getLogger()->info($queryString, $context);
                }
            }
        }
    }

    /**
     * @param string $query
     * @return \PDOStatement
     */
    private function prepare(string $query): \PDOStatement
    {
        $statement = $this->tScope->getPrepared($query);

        if ($statement === null) {
            $statement = $this->getPDO()->prepare($query);
            $this->tScope->setPrepared($query, $statement);
        }

        return $statement;
    }

    /**
     * @param ParameterInterface[] $parameters
     * @return array
     */
    private function flattenParameters(array $parameters): array
    {
        $result = [];

        $index = 0;
        foreach ($parameters as $name => $parameter) {
            if (is_string($name)) {
                $index = $name;
            } else {
                $index++;
            }

            if (!$parameter instanceof ParameterInterface) {
                $parameter = new Parameter($parameter);
            }

            if ($parameter->getValue() instanceof \DateTimeInterface) {
                $result[$index] = $parameter->withValue($this->formatDatetime($parameter->getValue()));
                continue;
            }

            if (!$parameter->isArray()) {
                $result[$index] = $parameter;
                continue;
            }

            if (!is_numeric($name)) {
                throw new BuilderException('Array parameters can not be named');
            }

            foreach ($parameter->getValue() as $child) {
                if (!$child instanceof ParameterInterface) {
                    $child = new Parameter($child);
                }

                if ($child->getValue() instanceof \DateTimeInterface) {
                    $result[$index++] = $child->withValue($this->formatDatetime($child->getValue()));
                    continue;
                }

                $result[$index++] = $child;
            }
        }

        return $result;
    }
}
