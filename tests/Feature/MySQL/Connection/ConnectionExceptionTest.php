<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Connection;

use Cycle\Database\Tests\Feature\Common\Connection\ConnectionExceptionTest as CommonClass;

class ConnectionExceptionTest extends CommonClass
{
    /**
     * @see \Cycle\Database\Driver\MySQL\MySQLDriver::mapException()
     */
    public function reconnectableExceptionsProvider(): iterable
    {
        return [
            [new \Exception('server has gone away')],
            [new \Exception('broken pipe')],
            [new \Exception('Bad connection')],
            [new \Exception('packets out of order')],
            [new \Exception('disconnected by the server because of inactivity')],
            [new \Exception(code: 2001)],
            [new \Exception(code: 2099)],
        ];
    }
}
