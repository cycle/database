<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\SQLite;

use Spiral\Database\Driver\Driver;
use Spiral\Database\Exception\StatementException;
use Spiral\Database\Query\QueryBuilder;
use Throwable;

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
