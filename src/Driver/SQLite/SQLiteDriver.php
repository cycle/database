<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLite;

use Cycle\Database\Driver\Driver;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\QueryBuilder;
use Throwable;
use Spiral\Database\Driver\SQLite\SQLiteDriver as SpiralSQLiteDriver;

class SQLiteDriver extends Driver
{
    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        parent::__construct(
            $options,
            new SQLiteHandler(),
            new SQLiteCompiler('""'),
            QueryBuilder::defaultBuilder()
        );
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'SQLite';
    }

    /**
     * @inheritDoc
     */
    public function getSource(): string
    {
        // remove "sqlite:"
        return substr($this->getDSN(), 7);
    }

    /**
     * @inheritDoc
     */
    protected function mapException(Throwable $exception, string $query): StatementException
    {
        if ((int)$exception->getCode() === 23000) {
            return new StatementException\ConstrainException($exception, $query);
        }

        return new StatementException($exception, $query);
    }

    /**
     * {@inheritdoc}
     */
    protected function setIsolationLevel(string $level): void
    {
        if ($this->logger !== null) {
            $this->logger->alert(
                "Transaction isolation level is not fully supported by SQLite ({$level})"
            );
        }
    }
}
\class_alias(SQLiteDriver::class, SpiralSQLiteDriver::class, false);
