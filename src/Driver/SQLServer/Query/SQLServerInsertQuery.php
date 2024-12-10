<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer\Query;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\SQLServer\SQLServerDriver;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Exception\ReadonlyConnectionException;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\QueryParameters;
use Cycle\Database\Query\ReturningInterface;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\QueryInterface;
use Cycle\Database\StatementInterface;

class SQLServerInsertQuery extends InsertQuery implements ReturningInterface
{
    /**
     * @var SQLServerDriver|null
     */
    protected ?DriverInterface $driver = null;

    /**
     * @var list<FragmentInterface|non-empty-string>
     */
    protected array $returningColumns = [];

    public function withDriver(DriverInterface $driver, ?string $prefix = null): QueryInterface
    {
        $driver instanceof SQLServerDriver or throw new BuilderException(
            'SQLServer InsertQuery can be used only with SQLServer driver',
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
            if (\count($this->returningColumns) === 1) {
                return $result->fetchColumn();
            }
            return $result->fetch(StatementInterface::FETCH_ASSOC);
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
