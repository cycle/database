<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\Postgres;

use Spiral\Database\Driver\Driver;
use Spiral\Database\Driver\Postgres\Query\PostgresInsertQuery;
use Spiral\Database\Exception\DriverException;
use Spiral\Database\Exception\StatementException;
use Spiral\Database\Query\DeleteQuery;
use Spiral\Database\Query\QueryBuilder;
use Spiral\Database\Query\SelectQuery;
use Spiral\Database\Query\UpdateQuery;
use Throwable;

/**
 * Talks to postgres databases.
 */
class PostgresDriver extends Driver
{
    /**
     * Cached list of primary keys associated with their table names. Used by InsertBuilder to
     * emulate last insert id.
     *
     * @var array
     */
    private $primaryKeys = [];

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        // default query builder
        parent::__construct(
            $options,
            new PostgresHandler(),
            new PostgresCompiler('""'),
            new QueryBuilder(
                new SelectQuery(),
                new PostgresInsertQuery(),
                new UpdateQuery(),
                new DeleteQuery()
            )
        );
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'Postgres';
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
    public function getPrimaryKey(string $prefix, string $table): ?string
    {
        $name = $prefix . $table;
        if (isset($this->primaryKeys[$name])) {
            return $this->primaryKeys[$name];
        }

        if (!$this->getSchemaHandler()->hasTable($name)) {
            throw new DriverException(
                "Unable to fetch table primary key, no such table '{$name}' exists"
            );
        }

        $this->primaryKeys[$name] = $this->getSchemaHandler()
            ->getSchema($table, $prefix)
            ->getPrimaryKeys();

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
    public function resetPrimaryKeys(): void
    {
        $this->primaryKeys = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function createPDO(): \PDO
    {
        // spiral is purely UTF-8
        $pdo = parent::createPDO();
        $pdo->exec("SET NAMES 'UTF-8'");

        return $pdo;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapException(Throwable $exception, string $query): StatementException
    {
        $message = strtolower($exception->getMessage());

        if (
            strpos($message, '0800') !== false
            || strpos($message, '080P') !== false
            || strpos($message, 'connection') !== false
        ) {
            return new StatementException\ConnectionException($exception, $query);
        }

        if ((int)$exception->getCode() >= 23000 && (int)$exception->getCode() < 24000) {
            return new StatementException\ConstrainException($exception, $query);
        }

        return new StatementException($exception, $query);
    }
}
