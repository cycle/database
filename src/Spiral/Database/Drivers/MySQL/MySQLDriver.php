<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Drivers\MySQL;

use PDO;
use Psr\Log\LoggerInterface;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\Drivers\MySQL\Schemas\MySQLTable;
use Spiral\Database\Entities\AbstractHandler;
use Spiral\Database\Entities\Driver;
use Spiral\Database\Exceptions\ConnectionException;
use Spiral\Database\Exceptions\QueryException;

/**
 * Talks to mysql databases.
 */
class MySQLDriver extends Driver
{
    /**
     * Driver type.
     */
    const TYPE = DatabaseInterface::MYSQL;

    /**
     * Driver schemas.
     */
    const TABLE_SCHEMA_CLASS = MySQLTable::class;

    /**
     * Query compiler class.
     */
    const QUERY_COMPILER = MySQLCompiler::class;

    /**
     * {@inheritdoc}
     */
    protected $pdoOptions = [
        PDO::ATTR_CASE               => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"',
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    /**
     * {@inheritdoc}
     */
    public function identifier(string $identifier): string
    {
        return $identifier == '*' ? '*' : '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable(string $name): bool
    {
        $query = "SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = ? AND `table_name` = ?";

        return (bool)$this->query($query, [$this->getSource(), $name])->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function truncateData(string $table)
    {
        $this->statement("TRUNCATE TABLE {$this->identifier($table)}");
    }

    /**
     * {@inheritdoc}
     */
    public function tableNames(): array
    {
        $result = [];
        foreach ($this->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM) as $row) {
            $result[] = $row[0];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler(LoggerInterface $logger = null): AbstractHandler
    {
        return new MySQLHandler($this, $logger);
    }

    /**
     * {@inheritdoc}
     *
     * @see https://dev.mysql.com/doc/refman/5.6/en/error-messages-client.html#error_cr_conn_host_error
     */
    protected function clarifyException(\PDOException $exception, string $query): QueryException
    {
        if ($exception->getCode() > 2000) {
            return new ConnectionException($exception, $query);
        }

        return new QueryException($exception, $query);
    }
}