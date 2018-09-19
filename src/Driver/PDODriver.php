<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Driver;

use PDO;
use Psr\Log\LoggerAwareInterface;
use Spiral\Database\Exception\ConnectionException;
use Spiral\Database\Exception\DriverException;
use Spiral\Database\Exception\QueryException;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;
use Spiral\Database\Query\Interpolator;
use Spiral\Database\QueryStatement;
use Spiral\Logger\Traits\LoggerTrait;

/**
 * Basic implementation of DBAL Driver, basically decorates PDO. Extends component to provide access
 *  to functionality like shared loggers and benchmarking.
 */
abstract class PDODriver implements LoggerAwareInterface
{
    use LoggerTrait;

    /**
     * One of DatabaseInterface types, must be set on implementation.
     */
    const TYPE = null;

    /**
     * DateTime format to be used to perform automatic conversion of DateTime objects.
     *
     * @var string
     */
    const DATETIME = 'Y-m-d H:i:s';

    /**
     * Driver name.
     *
     * @var string
     */
    private $name = '';

    /**
     * @var PDO|null
     */
    private $pdo = null;

    /**
     * Connection configuration described in DBAL config file. Any driver can be used as data source
     * for multiple databases as table prefix and quotation defined on Database instance level.
     *
     * @var array
     */
    protected $options = [
        'profiling'  => false,

        //All datetime objects will be converted relative to this timezone (must match with DB timezone!)
        'timezone'   => 'UTC',

        //DSN
        'connection' => '',
        'username'   => '',
        'password'   => '',
        'options'    => [],
    ];

    /**
     * PDO connection options set.
     *
     * @var array
     */
    protected $pdoOptions = [
        PDO::ATTR_CASE             => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    /**
     * @param string $name
     * @param array  $options
     */
    public function __construct(string $name, array $options)
    {
        $this->name = $name;

        $this->options = $options + $this->options;

        if (!empty($options['options'])) {
            //PDO connection options has to be stored under key "options" of config
            $this->pdoOptions = $options['options'] + $this->pdoOptions;
        }
    }

    /**
     * Source name, can include database name or database file.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
        if (preg_match(
            '/(?:dbname|database)=([^;]+)/i', $this->options['connection'],
            $matches
        )) {
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
     * Enabled profiling will raise set of log messages and benchmarks associated with PDO queries.
     *
     * @param bool $enabled Enable or disable driver profiling.
     *
     * @return self
     */
    public function setProfiling(bool $enabled = true): PDODriver
    {
        $this->options['profiling'] = $enabled;

        return $this;
    }

    /**
     * Check if profiling mode is enabled.
     *
     * @return bool
     */
    public function isProfiling(): bool
    {
        return $this->options['profiling'];
    }

    /**
     * Force driver to connect.
     *
     * @return PDO
     *
     * @throws DriverException
     */
    public function connect(): PDO
    {
        if ($this->isConnected()) {
            return $this->pdo;
        }

        return $this->pdo = $this->createPDO();
    }

    /**
     * Disconnect driver.
     *
     * @return self
     */
    public function disconnect(): PDODriver
    {
        $this->pdo = null;

        return $this;
    }

    /**
     * Reconnect driver.
     *
     * @return self
     */
    public function reconnect(): PDODriver
    {
        $this->pdo = null;
        $this->connect();

        return $this;
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
     * Change PDO instance associated with driver. Returns new copy of driver.
     *
     * @param PDO $pdo
     *
     * @return self
     *
     * @deprecated
     */
    public function withPDO(PDO $pdo): PDODriver
    {
        $driver = clone $this;
        $driver->pdo = $pdo;

        return $driver;
    }

    /**
     * Get associated PDO connection. Will automatically connect if such connection does not exists.
     *
     * @return PDO
     */
    public function getPDO(): PDO
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        return $this->pdo;
    }

    /**
     * Driver specific database/table identifier quotation.
     *
     * @param string $identifier
     *
     * @return string
     */
    public function identifier(string $identifier): string
    {
        return $identifier == '*' ? '*' : '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * Quote value using PDO.
     *
     * @param mixed $value
     * @param int   $type Parameter type.
     *
     * @return string
     */
    public function quote($value, int $type = PDO::PARAM_STR): string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $this->normalizeTimestamp($value);
        }

        return $this->getPDO()->quote($value, $type);
    }

    /**
     * Wraps PDO query method with custom representation class.
     *
     * @param string $statement
     * @param array  $parameters
     *
     * @return QueryStatement
     */
    public function query(string $statement, array $parameters = []): QueryStatement
    {
        //Forcing specific return class
        $result = $this->statement($statement, $parameters, QueryStatement::class);

        /**
         * @var QueryStatement $result
         */
        return $result;
    }

    /**
     * Create instance of PDOStatement using provided SQL query and set of parameters and execute
     * it. Will attempt singular reconnect.
     *
     * @param string $query
     * @param array  $parameters Parameters to be binded into query.
     * @param string $class      Class to be used to represent results.
     *
     * @return \PDOStatement
     *
     * @throws QueryException
     */
    public function statement(
        string $query,
        array $parameters = [],
        $class = QueryStatement::class
    ): \PDOStatement {
        try {
            return $this->runStatement($query, $parameters, $class);
        } catch (ConnectionException $e) {
            $this->reconnect();

            return $this->runStatement($query, $parameters, $class);
        }
    }

    /**
     * Create instance of PDOStatement using provided SQL query and set of parameters and execute
     * it.
     *
     * @param string $query
     * @param array  $parameters Parameters to be binded into query.
     * @param string $class      Class to be used to represent results.
     *
     * @return \PDOStatement
     *
     * @throws QueryException
     */
    public function runStatement(
        string $query,
        array $parameters = [],
        $class = QueryStatement::class
    ): \PDOStatement {
        try {
            //Filtered and normalized parameters
            $parameters = $this->flattenParameters($parameters);

            if ($this->isProfiling()) {
                $queryString = Interpolator::interpolate($query, $parameters);
            }

            //PDOStatement instance (prepared)
            $pdoStatement = $this->prepare($query, $class);

            //Mounting all input parameters
            $pdoStatement = $this->bindParameters($pdoStatement, $parameters);

            $pdoStatement->execute();

            //Only exists if profiling on
            if (!empty($queryString)) {
                //This is place you can use to handle ALL sql messages passed thought the driver
                $this->getLogger()->info($queryString, compact('query', 'parameters'));
            }
        } catch (\PDOException $e) {
            if (empty($queryString)) {
                $queryString = Interpolator::interpolate($query, $parameters);
            }

            //Logging error even when no profiling is enabled
            $this->getLogger()->error($queryString, compact('query', 'parameters'));

            //Logging error even when no profiling is enabled
            $this->getLogger()->alert($e->getMessage());

            //Converting exception into query or integrity exception
            throw $this->clarifyException($e, $queryString);
        }

        return $pdoStatement;
    }

    /**
     * Get prepared PDO statement.
     *
     * @param string $statement Query statement.
     * @param string $class     Class to represent PDO statement.
     *
     * @return \PDOStatement
     */
    public function prepare(string $statement, $class = QueryStatement::class): \PDOStatement
    {
        $pdo = $this->getPDO();

        $pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, [$class]);

        return $pdo->prepare($statement);
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

        if ($this->isProfiling()) {
            $this->getLogger()->debug("Given insert ID: {$result}");
        }

        return $result;
    }

    /**
     * Prepare set of query builder/user parameters to be send to PDO. Must convert DateTime
     * instances into valid database timestamps and resolve values of ParameterInterface.
     *
     * Every value has to wrapped with parameter interface.
     *
     * @param array $parameters
     *
     * @return ParameterInterface[]
     *
     * @throws DriverException
     */
    public function flattenParameters(array $parameters): array
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

                //Flattening arrays
                $nestedParameters = $parameter->flatten();

                /**
                 * @var ParameterInterface $parameter []
                 */
                foreach ($nestedParameters as &$nestedParameter) {
                    if ($nestedParameter->getValue() instanceof \DateTimeInterface) {

                        //Original parameter must not be altered
                        $nestedParameter = $nestedParameter->withValue(
                            $this->normalizeTimestamp($nestedParameter->getValue())
                        );
                    }

                    unset($nestedParameter);
                }

                //Quick and dirty
                $flatten = array_merge($flatten, $nestedParameters);

            } else {
                if ($parameter->getValue() instanceof \DateTimeInterface) {
                    //Original parameter must not be altered
                    $parameter = $parameter->withValue(
                        $this->normalizeTimestamp($parameter->getValue())
                    );
                }

                if (is_numeric($key)) {
                    //Numeric keys can be shifted
                    $flatten[] = $parameter;
                } else {
                    $flatten[$key] = $parameter;
                }
            }
        }

        return $flatten;
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'connection' => $this->options['connection'],
            'connected'  => $this->isConnected(),
            'profiling'  => $this->isProfiling(),
            'source'     => $this->getSource(),
            'options'    => $this->pdoOptions,
        ];
    }

    /**
     * Create instance of configured PDO class.
     *
     * @return PDO
     */
    protected function createPDO(): PDO
    {
        return new PDO(
            $this->options['connection'],
            $this->options['username'],
            $this->options['password'],
            $this->pdoOptions
        );
    }

    /**
     * Convert PDO exception into query or integrity exception.
     *
     * @param \PDOException $exception
     * @param string        $query
     *
     * @return QueryException
     */
    protected function clarifyException(\PDOException $exception, string $query): QueryException
    {
        return new QueryException($exception, $query);
    }

    /**
     * Convert DateTime object into local database representation. Driver will automatically force
     * needed timezone.
     *
     * @param \DateTimeInterface $value
     *
     * @return string
     */
    protected function normalizeTimestamp(\DateTimeInterface $value): string
    {
        //Immutable and prepared??
        $datetime = new \DateTime('now', $this->getTimezone());
        $datetime->setTimestamp($value->getTimestamp());

        return $datetime->format(static::DATETIME);
    }

    /**
     * Bind parameters into statement.
     *
     * @param \PDOStatement        $statement
     * @param ParameterInterface[] $parameters Named hash of ParameterInterface.
     *
     * @return \PDOStatement
     */
    private function bindParameters(\PDOStatement $statement, array $parameters): \PDOStatement
    {
        foreach ($parameters as $index => $parameter) {
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
}
