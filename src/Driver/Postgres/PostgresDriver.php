<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Driver\Postgres;

use Spiral\Database\DatabaseInterface;
use Spiral\Database\Driver\AbstractDriver;
use Spiral\Database\Driver\HandlerInterface;
use Spiral\Database\Driver\Postgres\Query\PostgresInsertQuery;
use Spiral\Database\Driver\Postgres\Schema\PostgresTable;
use Spiral\Database\Exception\DriverException;
use Spiral\Database\Exception\StatementException;
use Spiral\Database\Query\InsertQuery;

/**
 * Talks to postgres databases.
 */
class PostgresDriver extends AbstractDriver
{
    protected const TYPE               = DatabaseInterface::POSTGRES;
    protected const TABLE_SCHEMA_CLASS = PostgresTable::class;
    protected const QUERY_COMPILER     = PostgresCompiler::class;

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
    public function eraseData(string $table)
    {
        $this->execute("TRUNCATE TABLE {$this->identifier($table)}");
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
        $name = $prefix . $table;
        if (array_key_exists($name, $this->primaryKeys)) {
            return $this->primaryKeys[$name];
        }

        if (!$this->hasTable($name)) {
            throw new DriverException(
                "Unable to fetch table primary key, no such table '{$name}' exists"
            );
        }

        $this->primaryKeys[$name] = $this->getSchema($table, $prefix)->getPrimaryKeys();
        if (count($this->primaryKeys[$name]) === 1) {
            //We do support only single primary key
            $this->primaryKeys[$name] = $this->primaryKeys[$name][0];
        } else {
            $this->primaryKeys[$name] = null;
        }

        return $this->primaryKeys[$name];
    }

    /**
     * Reset primary keys cache.
     */
    public function resetPrimaryKeys()
    {
        $this->primaryKeys = [];
    }

    /**
     * {@inheritdoc}
     *
     * Postgres uses custom insert query builder in order to return value of inserted row.
     */
    public function insertQuery(string $prefix, string $table = null): InsertQuery
    {
        return new PostgresInsertQuery($this, $this->getCompiler($prefix), $table);
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
    public function getHandler(): HandlerInterface
    {
        return new PostgresHandler($this);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapException(\PDOException $exception, string $query): StatementException
    {
        if (
            strpos($exception->getMessage(), '0800') !== false
            || strpos($exception->getMessage(), '080P') !== false
        ) {
            return new QueryException\ConnectionException2($exception, $query);
        }

        if ($exception->getCode() >= 23000 && $exception->getCode() < 24000) {
            return new QueryException\ConstrainException2($exception, $query);
        }


        return new StatementException($exception, $query);
    }
}