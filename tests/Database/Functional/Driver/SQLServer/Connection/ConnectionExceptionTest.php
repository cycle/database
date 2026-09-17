<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Connection;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Connection\ConnectionExceptionTest as CommonClass;

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
            [new \Exception('Bad connection')],
            [new \Exception('SQLSTATE[08001]: [Microsoft][ODBC Driver 18 for SQL Server]TCP Provider: No connection could be made')],
            /** The ODBC driver translates its own strings, so only the SQLSTATE is dependable. */
            [new \Exception('SQLSTATE[08S01]: [Microsoft][ODBC Driver 18 for SQL Server]Поставщик TCP: Удаленный хост принудительно разорвал существующее подключение.')],
        ];
    }
}
