<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\SQLServer;

use PDO;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\Driver\Driver;
use Spiral\Database\Driver\HandlerInterface;
use Spiral\Database\Driver\SQLServer\Schema\SQLServerTable;
use Spiral\Database\Exception\DriverException;
use Spiral\Database\Exception\StatementException;
use Spiral\Database\Injection\ParameterInterface;

class SQLServerDriver extends Driver
{
    protected const TYPE                = DatabaseInterface::SQL_SERVER;
    protected const TABLE_SCHEMA_CLASS  = SQLServerTable::class;
    protected const QUERY_COMPILER      = SQLServerCompiler::class;
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
        parent::__construct($options);

        if ((int)$this->getPDO()->getAttribute(\PDO::ATTR_SERVER_VERSION) < 12) {
            throw new DriverException('SQLServer driver supports only 12+ version of SQLServer');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function identifier(string $identifier): string
    {
        return $identifier === '*' ? '*' : '[' . str_replace('[', '[[', $identifier) . ']';
    }

    /**
     * {@inheritdoc}
     */
    public function tableNames(): array
    {
        $query = "SELECT [table_name] FROM [information_schema].[tables] WHERE [table_type] = 'BASE TABLE'";

        $tables = [];
        foreach ($this->query($query)->fetchAll(PDO::FETCH_NUM) as $name) {
            $tables[] = $name[0];
        }

        return $tables;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable(string $name): bool
    {
        $query = "SELECT COUNT(*) FROM [information_schema].[tables]
            WHERE [table_type] = 'BASE TABLE' AND [table_name] = ?";

        return (bool)$this->query($query, [$name])->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function eraseData(string $table): void
    {
        $this->execute("TRUNCATE TABLE {$this->identifier($table)}");
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler(): HandlerInterface
    {
        return new SQLServerHandler($this);
    }

    /**
     * Bind parameters into statement. SQLServer need encoding to be specified for binary parameters.
     *
     * @param \PDOStatement        $statement
     * @param ParameterInterface[] $parameters Named hash of ParameterInterface.
     * @return \PDOStatement
     */
    protected function bindParameters(\PDOStatement $statement, iterable $parameters): \PDOStatement
    {
        foreach ($parameters as $index => $parameter) {
            if ($parameter->getType() === PDO::PARAM_LOB) {
                $value = $parameter->getValue();

                $statement->bindParam(
                    $index,
                    $value,
                    $parameter->getType(),
                    0,
                    PDO::SQLSRV_ENCODING_BINARY
                );

                continue;
            }

            $statement->bindValue($index, $parameter->getValue(), $parameter->getType());
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
        $this->getLogger()->info("Transaction: new savepoint 'SVP{$level}'");
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
        $this->getLogger()->info("Transaction: release savepoint 'SVP{$level}'");
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
        $this->getLogger()->info("Transaction: rollback savepoint 'SVP{$level}'");
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
