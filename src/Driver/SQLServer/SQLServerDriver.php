<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer;

use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Config\SQLServerDriverConfig;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\PDOStatementInterface;
use Cycle\Database\Driver\SQLServer\Query\SQLServerDeleteQuery;
use Cycle\Database\Driver\SQLServer\Query\SQLServerInsertQuery;
use Cycle\Database\Driver\SQLServer\Query\SQLServerSelectQuery;
use Cycle\Database\Driver\SQLServer\Query\SQLServerUpdateQuery;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Injection\ParameterInterface;
use Cycle\Database\Query\QueryBuilder;

class SQLServerDriver extends Driver
{
    /**
     * @var non-empty-string
     */
    protected const DATETIME = 'Y-m-d\TH:i:s.000';

    /**
     * @param SQLServerDriverConfig $config
     *
     * @throws DriverException
     */
    public static function create(DriverConfig $config): static
    {
        $driver = new static(
            $config,
            new SQLServerHandler(),
            new SQLServerCompiler('[]'),
            new QueryBuilder(
                new SQLServerSelectQuery(),
                new SQLServerInsertQuery(),
                new SQLServerUpdateQuery(),
                new SQLServerDeleteQuery(),
            ),
        );

        if ((int) $driver->getPDO()->getAttribute(\PDO::ATTR_SERVER_VERSION) < 12) {
            throw new DriverException('SQLServer driver supports only 12+ version of SQLServer');
        }

        return $driver;
    }

    public function getType(): string
    {
        return 'SQLServer';
    }

    /**
     * Bind parameters into statement. SQLServer need encoding to be specified for binary parameters.
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

            if ($type === \PDO::PARAM_LOB) {
                /** @psalm-suppress UndefinedConstant */
                $statement->bindParam(
                    $index,
                    $parameter,
                    $type,
                    0,
                    \PDO::SQLSRV_ENCODING_BINARY,
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
        $this->logger?->info("Transaction: new savepoint 'SVP{$level}'");

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
        $this->logger?->info("Transaction: release savepoint 'SVP{$level}'");

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
        $this->logger?->info("Transaction: rollback savepoint 'SVP{$level}'");

        $this->execute('ROLLBACK TRANSACTION ' . $this->identifier("SVP{$level}"));
    }

    protected function mapException(\Throwable $exception, string $query): StatementException
    {
        $message = \strtolower($exception->getMessage());


        if (
            \str_contains($message, '0800')
            || \str_contains($message, '080p')
            || \str_contains($message, 'connection')
        ) {
            return new StatementException\ConnectionException($exception, $query);
        }

        if ((int) $exception->getCode() === 23000) {
            return new StatementException\ConstrainException($exception, $query);
        }

        return new StatementException($exception, $query);
    }
}
