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
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\ReturningInterface;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\QueryInterface;
use Cycle\Database\Query\QueryParameters;
use Cycle\Database\StatementInterface;

/**
 * Postgres driver requires little bit different way to handle last insert id.
 */
class PostgresInsertQuery extends InsertQuery implements ReturningInterface
{
    /** @var PostgresDriver|null */
    protected ?DriverInterface $driver = null;

    /** @deprecated */
    protected string|FragmentInterface|null $returning = null;

    /** @var list<FragmentInterface|non-empty-string> */
    protected array $returningColumns = [];

    public function withDriver(DriverInterface $driver, ?string $prefix = null): QueryInterface
    {
        $driver instanceof PostgresDriver or throw new BuilderException(
            'Postgres InsertQuery can be used only with Postgres driver',
        );

        return parent::withDriver($driver, $prefix);
    }

    /**
     * Set returning column. If not set, the driver will detect PK automatically.
     */
    public function returning(string|FragmentInterface ...$columns): self
    {
        $columns === [] and throw new BuilderException('RETURNING clause should contain at least 1 column.');

        $this->returning = \count($columns) === 1 ? \reset($columns) : null;

        $this->returningColumns = \array_values($columns);

        return $this;
    }

    public function run(): mixed
    {
        $params = new QueryParameters();
        $queryString = $this->sqlStatement($params);

        $this->driver->isReadonly() and throw ReadonlyConnectionException::onWriteStatementExecution();

        $result = $this->driver->query($queryString, $params->getParameters());

        try {
            if ($this->returningColumns !== []) {
                if (\count($this->returningColumns) === 1) {
                    return $result->fetchColumn();
                }

                return $result->fetch(StatementInterface::FETCH_ASSOC);
            }

            // Return PK if no RETURNING clause is set
            if ($this->getPrimaryKey() !== null) {
                return $result->fetchColumn();
            }

            return null;
        } finally {
            $result->close();
        }
    }

    public function getTokens(): array
    {
        return [
            'table' => $this->table,
            'return' => $this->returningColumns !== [] ? $this->returningColumns : (array) $this->getPrimaryKey(),
            'columns' => $this->columns,
            'values' => $this->values,
        ];
    }

    private function getPrimaryKey(): ?string
    {
        try {
            return $this->driver?->getPrimaryKey($this->prefix, $this->table);
        } catch (\Throwable) {
            return null;
        }
    }
}
