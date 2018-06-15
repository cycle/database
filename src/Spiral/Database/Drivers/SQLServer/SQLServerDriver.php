<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Drivers\SQLServer;

use Interop\Container\ContainerInterface;
use PDO;
use Psr\Log\LoggerInterface;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\Drivers\SQLServer\Schemas\SQLServerTable;
use Spiral\Database\Entities\AbstractHandler;
use Spiral\Database\Entities\Driver;
use Spiral\Database\Exceptions\DriverException;

class SQLServerDriver extends Driver
{
    /**
     * Driver type.
     */
    const TYPE = DatabaseInterface::SQL_SERVER;

    /**
     * Driver schemas.
     */
    const TABLE_SCHEMA_CLASS = SQLServerTable::class;

    /**
     * Query compiler class.
     */
    const QUERY_COMPILER = SQLServerCompiler::class;

    /**
     * DateTime format to be used to perform automatic conversion of DateTime objects.
     *
     * @var string
     */
    const DATETIME = 'Y-m-d\TH:i:s.000';

    /**
     * @var array
     */
    protected $pdoOptions = [
        PDO::ATTR_CASE              => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];

    /**
     * {@inheritdoc}
     *
     * @throws DriverException
     */
    public function __construct($name, array $options, ContainerInterface $container)
    {
        parent::__construct($name, $options, $container);

        if ((int)$this->getPDO()->getAttribute(\PDO::ATTR_SERVER_VERSION) < 12) {
            throw new DriverException("SQLServer driver supports only 12+ version of SQLServer");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function identifier(string $identifier): string
    {
        return $identifier == '*' ? '*' : '[' . str_replace('[', '[[', $identifier) . ']';
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable(string $name): bool
    {
        $query = "SELECT COUNT(*) FROM [information_schema].[tables] WHERE [table_type] = 'BASE TABLE' AND [table_name] = ?";

        return (bool)$this->query($query, [$name])->fetchColumn();
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
    public function getHandler(LoggerInterface $logger = null): AbstractHandler
    {
        return new SQLServerHandler($this, $logger);
    }

    /**
     * Create nested transaction save point.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param string $name Savepoint name/id, must not contain spaces and be valid database
     *                     identifier.
     */
    protected function savepointCreate(string $name)
    {
        if ($this->isProfiling()) {
            $this->logger()->info("Transaction: new savepoint 'SVP{$name}'");
        }

        $this->statement('SAVE TRANSACTION ' . $this->identifier("SVP{$name}"));
    }

    /**
     * Commit/release savepoint.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param string $name Savepoint name/id, must not contain spaces and be valid database
     *                     identifier.
     */
    protected function savepointRelease(string $name)
    {
        if ($this->isProfiling()) {
            $this->logger()->info("Transaction: release savepoint 'SVP{$name}'");
        }

        //SQLServer automatically commits nested transactions with parent transaction
    }

    /**
     * Rollback savepoint.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param string $name Savepoint name/id, must not contain spaces and be valid database
     *                     identifier.
     */
    protected function savepointRollback(string $name)
    {
        if ($this->isProfiling()) {
            $this->logger()->info("Transaction: rollback savepoint 'SVP{$name}'");
        }

        $this->statement('ROLLBACK TRANSACTION ' . $this->identifier("SVP{$name}"));
    }
}