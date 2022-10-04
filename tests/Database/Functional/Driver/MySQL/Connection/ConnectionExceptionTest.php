<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Connection;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Connection\ConnectionExceptionTest as CommonClass;
use Exception;

/**
 * @group driver
 * @group driver-mysql
 */
class ConnectionExceptionTest extends CommonClass
{
    public const DRIVER = 'mysql-mock';

    /**
     * @see \Cycle\Database\Driver\MySQL\MySQLDriver::mapException()
     */
    public function reconnectableExceptionsProvider(): iterable
    {
        return [
            [new Exception('server has gone away')],
            [new Exception('broken pipe')],
            [new Exception('Bad connection')],
            [new Exception('packets out of order')],
            [new Exception(code: 2001)],
            [new Exception(code: 2099)],
        ];
    }
}
