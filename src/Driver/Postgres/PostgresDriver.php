<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Driver\Postgres;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Spiral\Core\MemoryInterface;
use Spiral\Database\Query\InsertQuery;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\Driver\Postgres\Schema\PostgresTable;
use Spiral\Database\Entity\AbstractHandler;
use Spiral\Database\Entity\Driver;
use Spiral\Database\Exception\DriverException;

/**
 * Talks to postgres databases.
 */
class PostgresDriver extends Driver
{
    /**
     * Driver type.
     */
    const TYPE = DatabaseInterface::POSTGRES;

    /**
     * Driver schemas.
     */
    const TABLE_SCHEMA_CLASS = PostgresTable::class;

    /**
     * Query compiler class.
     */
    const QUERY_COMPILER = PostgresCompiler::class;

    /**
     * Cached list of primary keys associated with their table names. Used by InsertBuilder to
     * emulate last insert id.
     *
     * @var array
     */
    private $primaryKeys = [];

    /**
     * Used to store information about associated primary keys.
     *
     * @var MemoryInterface
     */
    protected $memory = null;

    /**
     * @param string             $name
     * @param array              $options
     * @param ContainerInterface $container
     * @param MemoryInterface    $memory Optional.
     */
    public function __construct(
        $name,
        array $options,
        ContainerInterface $container = null,
        MemoryInterface $memory = null
    ) {
        parent::__construct($name, $options, $container);
        $this->memory = $memory;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable(string $name): bool
    {
        $query = "SELECT COUNT(table_name) FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' AND table_name = ?";

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
        $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'";

        $tables = [];
        foreach ($this->query($query) as $row) {
            $tables[] = $row['table_name'];
        }

        return $tables;
    }

    /**
     * Get singular primary key associated with desired table. Used to emulate last insert id.
     *
     * @param string $prefix Database prefix if any.
     * @param string $table  Fully specified table name, including postfix.
     *
     * @return string|null
     *
     * @throws DriverException
     */
    public function getPrimary(string $prefix, string $table)
    {
        if (!empty($this->memory) && empty($this->primaryKeys)) {
            $this->primaryKeys = (array)$this->memory->loadData($this->getSource() . '.keys');
        }

        if (!empty($this->primaryKeys) && array_key_exists($table, $this->primaryKeys)) {
            return $this->primaryKeys[$table];
        }

        if (!$this->hasTable($prefix . $table)) {
            throw new DriverException(
                "Unable to fetch table primary key, no such table '{$prefix}{$table}' exists"
            );
        }

        $this->primaryKeys[$table] = $this->tableSchema($table, $prefix)->getPrimaryKeys();
        if (count($this->primaryKeys[$table]) === 1) {
            //We do support only single primary key
            $this->primaryKeys[$table] = $this->primaryKeys[$table][0];
        } else {
            $this->primaryKeys[$table] = null;
        }

        //Caching
        if (!empty($this->memory)) {
            $this->memory->saveData($this->getSource() . '.keys', $this->primaryKeys);
        }

        return $this->primaryKeys[$table];
    }

    /**
     * {@inheritdoc}
     *
     * Postgres uses custom insert query builder in order to return value of inserted row.
     */
    public function insertBuilder(string $prefix, array $parameters = []): InsertQuery
    {
        return $this->container->make(
            PostgresInsertQuery::class,
            ['driver' => $this, 'compiler' => $this->queryCompiler($prefix),] + $parameters
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createPDO(): \PDO
    {
        //Spiral is purely UTF-8
        $pdo = parent::createPDO();
        $pdo->exec("SET NAMES 'UTF-8'");

        return $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler(LoggerInterface $logger = null): AbstractHandler
    {
        return new PostgresHandler($this, $logger);
    }
}
