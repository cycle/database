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
use Spiral\Database\Driver\Traits\BuilderTrait;
use Spiral\Database\Driver\Traits\ProfilingTrait;
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
    use ProfilingTrait, BuilderTrait;

    // One of DatabaseInterface types, must be set on implementation.
    protected const TYPE = "@undefined";

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
        'profiling'  => false,

        // allow reconnects
        'reconnect'  => false,

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

    /**
     * Transaction level (count of nested transactions). Not all drives can support nested
     * transactions.
     *
     * @var int
     */
    private $transactionLevel = 0;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $options['options'] = ($options['options'] ?? []) + static::DEFAULT_PDO_OPTIONS;
        $this->options = $options + $this->options;

        if (!empty($this->options['profiling'])) {
            $this->setProfiling(true);
        }
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
    public function connect()
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
        return !empty($this->pdo);
    }

    /**
     * Disconnect driver.
     */
    public function disconnect()
    {
        $this->pdo = null;
        $this->transactionLevel = 0;
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
        return $identifier == '*' ? '*' : '"' . str_replace('"', '""', $identifier) . '"';
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
     * @param string|null $sequence Name of the sequence object from which the ID should be
     *                              returned.
     *
     * @return mixed
     */
    public function lastInsertID(string $sequence = null)
    {
        $pdo = $this->getPDO();
        $result = $sequence ? (int)$pdo->lastInsertId($sequence) : (int)$pdo->lastInsertId();

        $this->isProfiling() && $this->getLogger()->debug("Given insert ID: {$result}");

        return $result;
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
    protected function statement(string $query, array $parameters = [], bool $retry = null): StatementInterface
    {
        if (is_null($retry)) {
            $retry = $this->options['reconnect'];
        }

        try {
            $flatten = $this->flattenParameters($parameters);

            //Mounting all input parameters
            $statement = $this->bindParameters($this->prepare($query), $flatten);
            $statement->execute();

            if ($this->isProfiling()) {
                $this->getLogger()->info(
                    Interpolator::interpolate($query, $parameters),
                    compact('query', 'parameters')
                );
            }
        } catch (\PDOException $e) {
            $queryString = Interpolator::interpolate($query, $parameters);

            $this->getLogger()->error($queryString, compact('query', 'parameters'));
            $this->getLogger()->alert($e->getMessage());

            //Converting exception into query or integrity exception
            $e = $this->mapException($e, $queryString);

            if (
                $e instanceof StatementException\ConnectionException
                && $this->transactionLevel === 0
                && $retry
            ) {
                // retrying
                return $this->statement($query, $parameters, false);
            }

            throw $e;
        }

        return new Statement($statement);
    }

    /**
     * @param string $query
     * @return \PDOStatement
     */
    protected function prepare(string $query): \PDOStatement
    {
        return $this->getPDO()->prepare($query);
    }

    /**
     * Prepare set of query builder/user parameters to be send to PDO. Must convert DateTime
     * instances into valid database timestamps and resolve values of ParameterInterface.
     *
     * Every value has to wrapped with parameter interface.
     *
     * @param array $parameters
     * @return ParameterInterface[]
     *
     * @throws DriverException
     */
    protected function flattenParameters(array $parameters): array
    {
        $flatten = [];
        foreach ($parameters as $key => $parameter) {
            if (!$parameter instanceof ParameterInterface) {
                //Let's wrap value
                $parameter = new Parameter($parameter, Parameter::DETECT_TYPE);
            }

            if ($parameter->isArray()) {
                if (!is_numeric($key)) {
                    throw new DriverException("Array parameters can not be named");
                }

                /**
                 * @var ParameterInterface $parameter []
                 */
                foreach ($parameter->flatten() as $nestedParameter) {
                    if ($nestedParameter->getValue() instanceof \DateTimeInterface) {
                        //Original parameter must not be altered
                        $nestedParameter = $nestedParameter->withValue(
                            $this->formatDatetime($nestedParameter->getValue())
                        );
                    }

                    $flatten[] = $nestedParameter;
                }

                continue;
            }

            if ($parameter->getValue() instanceof \DateTimeInterface) {
                //Original parameter must not be altered
                $parameter = $parameter->withValue(
                    $this->formatDatetime($parameter->getValue())
                );
            }

            if (is_numeric($key)) {
                //Numeric keys can be shifted
                $flatten[] = $parameter;
            } else {
                $flatten[$key] = $parameter;
            }
        }

        return $flatten;
    }

    /**
     * Bind parameters into statement.
     *
     * @param \PDOStatement        $statement
     * @param ParameterInterface[] $parameters Named hash of ParameterInterface.
     * @return \PDOStatement
     */
    protected function bindParameters(\PDOStatement $statement, array $parameters): \PDOStatement
    {
        foreach ($parameters as $index => $parameter) {
            if ($parameter->getType() === PDO::PARAM_NULL) {
                // must be compiled on SQL level
                continue;
            }

            if (is_numeric($index)) {
                //Numeric, @see http://php.net/manual/en/pdostatement.bindparam.php
                $statement->bindValue($index + 1, $parameter->getValue(), $parameter->getType());
            } else {
                //Named
                $statement->bindValue($index, $parameter->getValue(), $parameter->getType());
            }
        }

        return $statement;
    }

    /**
     * Convert PDO exception into query or integrity exception.
     *
     * @param \PDOException $exception
     * @param string        $query
     * @return StatementException
     */
    abstract protected function mapException(
        \PDOException $exception,
        string $query
    ): StatementException;

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

        if ($this->transactionLevel == 1) {
            if (!empty($isolationLevel)) {
                $this->isolationLevel($isolationLevel);
            }

            $this->isProfiling() && $this->getLogger()->info('Begin transaction');

            try {
                return $this->getPDO()->beginTransaction();
            } catch (\PDOException $e) {
                $e = $this->mapException($e, "BEGIN TRANSACTION");

                if (
                    $e instanceof StatementException\ConnectionException
                    && $this->options['reconnect']
                ) {
                    try {
                        return $this->getPDO()->beginTransaction();
                    } catch (\PDOException $e) {
                        throw $this->mapException($e, "BEGIN TRANSACTION");
                    }
                }
            }
        }

        $this->savepointCreate($this->transactionLevel);

        return true;
    }

    /**
     * Commit the active database transaction.
     *
     * @return bool
     */
    public function commitTransaction(): bool
    {
        --$this->transactionLevel;

        if ($this->transactionLevel == 0) {
            $this->isProfiling() && $this->getLogger()->info('Commit transaction');

            try {
                return $this->getPDO()->commit();
            } catch (\PDOException $e) {
                throw $this->mapException($e, "COMMIT TRANSACTION");
            }
        }

        $this->savepointRelease($this->transactionLevel + 1);

        return true;
    }

    /**
     * Rollback the active database transaction.
     *
     * @return bool
     */
    public function rollbackTransaction(): bool
    {
        --$this->transactionLevel;

        if ($this->transactionLevel == 0) {
            $this->isProfiling() && $this->getLogger()->info('Rollback transaction');

            try {
                return $this->getPDO()->rollBack();
            } catch (\PDOException $e) {
                throw $this->mapException($e, "ROLLBACK TRANSACTION");
            }
        }

        $this->savepointRollback($this->transactionLevel + 1);

        return true;
    }

    /**
     * Set transaction isolation level, this feature may not be supported by specific database
     * driver.
     *
     * @param string $level
     */
    protected function isolationLevel(string $level)
    {
        if (!empty($level)) {
            $this->isProfiling() && $this->getLogger()->info("Set transaction isolation level to '{$level}'");
            $this->execute("SET TRANSACTION ISOLATION LEVEL {$level}");
        }
    }

    /**
     * Create nested transaction save point.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param int $level Savepoint name/id, must not contain spaces and be valid database identifier.
     */
    protected function savepointCreate(int $level)
    {
        $this->isProfiling() && $this->getLogger()->info("Transaction: new savepoint 'SVP{$level}'");
        $this->execute('SAVEPOINT ' . $this->identifier("SVP{$level}"));
    }

    /**
     * Commit/release savepoint.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param int $level Savepoint name/id, must not contain spaces and be valid database identifier.
     */
    protected function savepointRelease(int $level)
    {
        $this->isProfiling() && $this->getLogger()->info("Transaction: release savepoint 'SVP{$level}'");
        $this->execute('RELEASE SAVEPOINT ' . $this->identifier("SVP{$level}"));
    }

    /**
     * Rollback savepoint.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param int $level Savepoint name/id, must not contain spaces and be valid database identifier.
     */
    protected function savepointRollback(int $level)
    {
        $this->isProfiling() && $this->getLogger()->info("Transaction: rollback savepoint 'SVP{$level}'");
        $this->execute('ROLLBACK TO SAVEPOINT ' . $this->identifier("SVP{$level}"));
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'connection' => $this->options['connection'] ?? $this->options['dsn'],
            'connected'  => $this->isConnected(),
            'profiling'  => $this->isProfiling(),
            'source'     => $this->getSource(),
            'options'    => $this->options['options'],
        ];
    }

    /**
     * Disconnect and destruct.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Create instance of configured PDO class.
     *
     * @return PDO
     */
    protected function createPDO(): PDO
    {
        return new PDO(
            $this->options['connection'] ?? $this->options['dsn'],
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
}
