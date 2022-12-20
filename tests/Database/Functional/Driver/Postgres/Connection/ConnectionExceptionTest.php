<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Connection;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Connection\ConnectionExceptionTest as CommonClass;
use Exception;

/**
 * @group driver
 * @group driver-postgres
 */
class ConnectionExceptionTest extends CommonClass
{
    public const DRIVER = 'postgres-mock';

    /**
     * @see \Cycle\Database\Driver\Postgres\PostgresDriver::mapException()
     */
    public function reconnectableExceptionsProvider(): iterable
    {
        return [
            [new Exception('eof detected')],
            [new Exception('broken pipe')],
            [new Exception('0800')],
            [new Exception('080P')],
            [new Exception('Bad connection')],
            /** Case from {@link https://github.com/cycle/database/issues/75} */
            [new Exception('server closed the connection unexpectedly')],
        ];
    }
}
