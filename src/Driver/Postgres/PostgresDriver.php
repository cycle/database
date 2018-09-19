<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Driver\Postgres;

use Spiral\Database\DatabaseInterface;
use Spiral\Database\Driver\AbstractHandler;
use Spiral\Database\Driver\Driver;
use Spiral\Database\Driver\Postgres\Schema\PostgresTable;
use Spiral\Database\Exception\DriverException;
use Spiral\Database\Query\InsertQuery;

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

        return $this->primaryKeys[$table];
    }

    /**
     * {@inheritdoc}
     *
     * Postgres uses custom insert query builder in order to return value of inserted row.
     */
    public function insertBuilder(string $prefix, array $parameters = []): InsertQuery
    {
        return new PostgresInsertQuery(
            $this,
            $this->queryCompiler($prefix),
            $parameters['table'] ?? ''
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
    public function getHandler(): AbstractHandler
    {
        return new PostgresHandler($this);
    }
}
