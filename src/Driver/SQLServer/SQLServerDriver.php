<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\SQLServer;

use DateTimeInterface;
use PDO;
use PDOStatement;
use Spiral\Database\Driver\Driver;
use Spiral\Database\Exception\DriverException;
use Spiral\Database\Exception\StatementException;
use Spiral\Database\Injection\ParameterInterface;
use Spiral\Database\Query\QueryBuilder;

class SQLServerDriver extends Driver
{
    protected const DATETIME            = 'Y-m-d\TH:i:s.000';
    protected const DEFAULT_PDO_OPTIONS = [
        PDO::ATTR_CASE              => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_STRINGIFY_FETCHES => false
    ];

    /**
     * {@inheritdoc}
     *
     * @throws DriverException
     */
    public function __construct(array $options)
    {
        parent::__construct(
            $options,
            new SQLServerHandler(),
            new SQLServerCompiler('[]'),
            QueryBuilder::defaultBuilder()
        );

        if ((int)$this->getPDO()->getAttribute(\PDO::ATTR_SERVER_VERSION) < 12) {
            throw new DriverException('SQLServer driver supports only 12+ version of SQLServer');
        }
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'SQLServer';
    }

    /**
     * Bind parameters into statement. SQLServer need encoding to be specified for binary parameters.
     *
     * @param PDOStatement $statement
     * @param array        $parameters
     * @return PDOStatement
     */
    protected function bindParameters(
        PDOStatement $statement,
        iterable $parameters
    ): PDOStatement {
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

            if ($type === PDO::PARAM_LOB) {
                $statement->bindParam(
                    $index,
                    $parameter,
                    $type,
                    0,
                    PDO::SQLSRV_ENCODING_BINARY
                );

                unset($parameter);
                continue;
            }

            // numeric, @see http://php.net/manual/en/pdostatement.bindparam.php
            $statement->bindValue($index, $parameter, $type);
            unset($parameter);
        }

        return $statement;
    }

    /**
     * Create nested transaction save point.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param int $level   Savepoint name/id, must not contain spaces and be valid database
     *                     identifier.
     */
    protected function createSavepoint(int $level): void
    {
        if ($this->logger !== null) {
            $this->logger->info("Transaction: new savepoint 'SVP{$level}'");
        }

        $this->execute('SAVE TRANSACTION ' . $this->identifier("SVP{$level}"));
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
        // SQLServer automatically commits nested transactions with parent transaction
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

        $this->execute('ROLLBACK TRANSACTION ' . $this->identifier("SVP{$level}"));
    }

    /**
     * {@inheritdoc}
     */
    protected function mapException(\Throwable $exception, string $query): StatementException
    {
        $message = strtolower($exception->getMessage());

        if (
            strpos($message, '0800') !== false
            || strpos($message, '080P') !== false
            || strpos($message, 'connection') !== false
        ) {
            return new StatementException\ConnectionException($exception, $query);
        }

        if ((int)$exception->getCode() === 23000) {
            return new StatementException\ConstrainException($exception, $query);
        }

        return new StatementException($exception, $query);
    }
}
