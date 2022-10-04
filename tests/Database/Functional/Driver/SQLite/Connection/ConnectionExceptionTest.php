<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Connection;

// phpcs:ignore
use Cycle\Database\Exception\StatementException\ConnectionException;
use Cycle\Database\Tests\Functional\Driver\Common\Connection\ConnectionExceptionTest as CommonClass;
use Exception;
use RuntimeException;

/**
 * @group driver
 * @group driver-sqlite
 */
class ConnectionExceptionTest extends CommonClass
{
    public const DRIVER = 'sqlite-mock';

    /**
     * There is no cases for ConnectionException.
     *
     * @see \Cycle\Database\Driver\SQLite\SQLiteDriver::mapException()
     */
    public function reconnectableExceptionsProvider(): iterable
    {
        return [];
    }
}
