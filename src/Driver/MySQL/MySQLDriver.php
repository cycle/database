<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\MySQL;

use PDO;
use Spiral\Database\Driver\Driver;
use Spiral\Database\Exception\StatementException;
use Spiral\Database\Query\QueryBuilder;

/**
 * Talks to mysql databases.
 */
class MySQLDriver extends Driver
{
    protected const DEFAULT_PDO_OPTIONS = [
        PDO::ATTR_CASE               => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"',
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        // default query builder
        parent::__construct(
            $options,
            new MySQLHandler(),
            new MySQLCompiler('``'),
            QueryBuilder::defaultBuilder()
        );
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'MySQL';
    }

    /**
     * {@inheritdoc}
     *
     * @see https://dev.mysql.com/doc/refman/5.6/en/error-messages-client.html#error_cr_conn_host_error
     */
    protected function mapException(\Throwable $exception, string $query): StatementException
    {
        if ((int)$exception->getCode() === 23000) {
            return new StatementException\ConstrainException($exception, $query);
        }

        $message = strtolower($exception->getMessage());

        if (
            strpos($message, 'server has gone away') !== false
            || strpos($message, 'broken pipe') !== false
            || strpos($message, 'connection') !== false
            || ((int)$exception->getCode() > 2000 && (int)$exception->getCode() < 2100)
        ) {
            return new StatementException\ConnectionException($exception, $query);
        }

        return new StatementException($exception, $query);
    }
}
