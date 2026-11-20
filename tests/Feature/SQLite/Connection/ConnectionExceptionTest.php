<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLite\Connection;

use Cycle\Database\Exception\StatementException\ConnectionException;
use Cycle\Database\Tests\Feature\Common\Connection\ConnectionExceptionTest as CommonClass;

class ConnectionExceptionTest extends CommonClass
{
    /**
     * There is no cases for ConnectionException: the tests that use them exclude SQLite.
     *
     * @see \Cycle\Database\Driver\SQLite\SQLiteDriver::mapException()
     */
    public function reconnectableExceptionsProvider(): iterable
    {
        return [];
    }
}
