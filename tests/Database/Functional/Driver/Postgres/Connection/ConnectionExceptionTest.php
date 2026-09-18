<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Connection;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Connection\ConnectionExceptionTest as CommonClass;

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
            [new \Exception('eof detected')],
            [new \Exception('broken pipe')],
            [new \Exception('Bad connection')],
            /** Case from {@link https://github.com/cycle/database/issues/75} */
            [new \Exception('server closed the connection unexpectedly')],
            [new \Exception('SQLSTATE[08006] [7] connection to server at "127.0.0.1", port 5432 failed')],
            [new \Exception('SQLSTATE[08P01]: Protocol violation: 7 ERROR:  invalid message format')],
            [new \Exception('SQLSTATE[57P01]: Admin shutdown: 7 FATAL:  terminating connection due to administrator command')],
            [new \Exception('SQLSTATE[53300]: Too many connections: 7 FATAL:  sorry, too many clients already')],
        ];
    }
}
