<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Driver\Traits;

use PDO;
use Psr\Log\LoggerInterface;
use Spiral\Database\Exception\DriverException;
use Spiral\Database\Exception\StatementException;
use Spiral\Database\Exception\StatementException\ConnectionException;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;
use Spiral\Database\Query\Interpolator;
use Spiral\Database\Statement;

trait PDOTrait
{
    /** @var PDO|null */
    protected $pdo;

    /**
     * Force driver connection.
     *
     * @throws DriverException
     */
    public function connect()
    {
        if (!$this->isConnected()) {
            $this->pdo = $this->createPDO();
            $this->pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [Statement::class]);
        }
    }

    /**
     * Disconnect driver.
     */
    public function disconnect()
    {
        $this->pdo = null;
    }

    /**
     * Reconnect driver.
     *
     * @throws DriverException
     */
    public function reconnect()
    {
        $this->pdo = null;
        $this->connect();
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
     * Wraps PDO query method with custom representation class.
     *
     * @param string $statement
     * @param array  $parameters
     * @return Statement
     *
     * @throws StatementException
     */
    public function query(string $statement, array $parameters = []): Statement
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
     * @param string $query
     * @param array  $parameters Parameters to be binded into query.
     * @return Statement
     *
     * @throws StatementException
     */
    protected function statement(string $query, array $parameters = []): Statement
    {
        try {
            return $this->runStatement($query, $parameters);
        } catch (ConnectionException $e) {
            $this->reconnect();

            return $this->runStatement($query, $parameters);
        }
    }

    /**
     * Create instance of PDOStatement using provided SQL query and set of parameters and execute
     * it.
     *
     * @param string $query
     * @param array  $parameters Parameters to be binded into query.
     * @return Statement
     *
     * @throws StatementException
     */
    protected function runStatement(string $query, array $parameters = []): Statement
    {
        try {
            //Mounting all input parameters
            $statement = $this->bindParameters(
                $this->getPDO()->prepare($query),
                $this->flattenParameters($parameters)
            );

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
            throw $this->mapException($e, $queryString);
        }

        return $statement;
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

                //Flattening arrays
                $nestedParameters = $parameter->flatten();

                /**
                 * @var ParameterInterface $parameter []
                 */
                foreach ($nestedParameters as &$nestedParameter) {
                    if ($nestedParameter->getValue() instanceof \DateTimeInterface) {
                        //Original parameter must not be altered
                        $nestedParameter = $nestedParameter->withValue(
                            $this->formatDatetime($nestedParameter->getValue())
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
        }

        return $flatten;
    }

    /**
     * Bind parameters into statement.
     *
     * @param Statement            $statement
     * @param ParameterInterface[] $parameters Named hash of ParameterInterface.
     * @return Statement
     */
    protected function bindParameters(Statement $statement, array $parameters): Statement
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

    /**
     * Check if profiling mode is enabled.
     *
     * @return bool
     */
    abstract public function isProfiling(): bool;

    /**
     * Create PDO connection.
     *
     * @return PDO
     *
     * @throws DriverException
     */
    abstract protected function createPDO(): PDO;

    /**
     * Convert DateTime object into local database representation. Driver will automatically force
     * needed timezone.
     *
     * @param \DateTimeInterface $value
     *
     * @return string
     */
    abstract protected function formatDatetime(\DateTimeInterface $value): string;

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
     * @return LoggerInterface
     */
    abstract protected function getLogger(): LoggerInterface;
}