<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Connection;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Connection\ConnectionExceptionTest as CommonClass;
use Exception;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ConnectionExceptionTest extends CommonClass
{
    public const DRIVER = 'sqlserver-mock';

    /**
     * @see \Cycle\Database\Driver\SQLServer\SQLServerDriver::mapException()
     */
    public function reconnectableExceptionsProvider(): iterable
    {
        return [
            [new Exception('0800')],
            [new Exception('080P')],
            [new Exception('Bad connection')],
        ];
    }
}
