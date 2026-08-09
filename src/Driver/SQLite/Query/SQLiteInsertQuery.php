<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLite\Query;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Exception\ReadonlyConnectionException;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\QueryInterface;
use Cycle\Database\Query\QueryParameters;
use Cycle\Database\Query\ReturningInterface;
use Cycle\Database\StatementInterface;

/**
 * SQLite supports the Postgres-compatible `RETURNING` clause since 3.35.0 (2021-03-12).
 */
class SQLiteInsertQuery extends InsertQuery implements ReturningInterface
{
    /** @var SQLiteDriver|null */
    protected ?DriverInterface $driver = null;

    /** @var list<FragmentInterface|non-empty-string> */
    protected array $returningColumns = [];

    public function withDriver(DriverInterface $driver, ?string $prefix = null): QueryInterface
    {
        $driver instanceof SQLiteDriver or throw new BuilderException(
            'SQLite InsertQuery can be used only with SQLite driver',
        );

        return parent::withDriver($driver, $prefix);
    }

    public function returning(string|FragmentInterface ...$columns): self
    {
        $columns === [] and throw new BuilderException('RETURNING clause should contain at least 1 column.');

        $this->returningColumns = \array_values($columns);

        return $this;
    }

    public function run(): mixed
    {
        if ($this->returningColumns === []) {
            return parent::run();
        }

        $params = new QueryParameters();
        $queryString = $this->sqlStatement($params);

        $this->driver->isReadonly() and throw ReadonlyConnectionException::onWriteStatementExecution();

        $result = $this->driver->query($queryString, $params->getParameters());

        try {
            return \count($this->returningColumns) === 1
                ? $result->fetchColumn()
                : $result->fetch(StatementInterface::FETCH_ASSOC);
        } finally {
            $result->close();
        }
    }

    public function getTokens(): array
    {
        return parent::getTokens() + [
            'return' => $this->returningColumns,
        ];
    }
}
