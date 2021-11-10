<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Query;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Exception\ReadonlyConnectionException;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\QueryInterface;
use Cycle\Database\Query\QueryParameters;
use Throwable;
use Spiral\Database\Driver\DriverInterface as SpiralDriverInterface;
use Spiral\Database\Driver\Postgres\Query\PostgresInsertQuery as SpiralPostgresInsertQuery;

interface_exists(SpiralDriverInterface::class);

/**
 * Postgres driver requires little bit different way to handle last insert id.
 */
class PostgresInsertQuery extends InsertQuery
{
    /** @var PostgresDriver */
    protected $driver;

    /** @var string|null */
    protected $returning;

    /**
     * @param DriverInterface $driver
     * @param string|null     $prefix
     * @return QueryInterface
     */
    public function withDriver(SpiralDriverInterface $driver, string $prefix = null): QueryInterface
    {
        if (!$driver instanceof PostgresDriver) {
            throw new BuilderException(
                'Postgres InsertQuery can be used only with Postgres driver'
            );
        }

        return parent::withDriver($driver, $prefix);
    }

    /**
     * Set returning column. If not set, the driver will detect PK automatically.
     *
     * @param string $column
     * @return $this
     */
    public function returning(string $column): self
    {
        $this->returning = $column;

        return $this;
    }

    /**
     * @return int|string|null
     */
    public function run()
    {
        $params = new QueryParameters();
        $queryString = $this->sqlStatement($params);

        if ($this->driver->isReadonly()) {
            throw ReadonlyConnectionException::onWriteStatementExecution();
        }

        $result = $this->driver->query($queryString, $params->getParameters());

        try {
            if ($this->getPrimaryKey() !== null) {
                return $result->fetchColumn();
            }

            return null;
        } finally {
            $result->close();
        }
    }

    /**
     * @return array
     */
    public function getTokens(): array
    {
        return [
            'table'   => $this->table,
            'return'  => $this->getPrimaryKey(),
            'columns' => $this->columns,
            'values'  => $this->values
        ];
    }

    /**
     * @return string
     */
    private function getPrimaryKey(): ?string
    {
        $primaryKey = $this->returning;
        if ($primaryKey === null && $this->driver !== null && $this->table !== null) {
            try {
                $primaryKey = $this->driver->getPrimaryKey($this->prefix, $this->table);
            } catch (Throwable $e) {
                return null;
            }
        }

        return $primaryKey;
    }
}
\class_alias(PostgresInsertQuery::class, SpiralPostgresInsertQuery::class, false);
