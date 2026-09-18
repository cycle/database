<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Driver;

use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Driver\SQLServer\SQLServerDriver;
use Cycle\Database\Exception\StatementException;
// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\DriverTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class DriverTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    public function testCreateDoesNotReachTheServer(): void
    {
        $config = clone self::$config[static::DRIVER];
        \assert($config instanceof DriverConfig);

        $connection = clone $config->connection;
        $connection->password = 'definitely not the password';
        $config->connection = $connection;

        $driver = SQLServerDriver::create($config);

        self::assertFalse($driver->isConnected());

        // The failure arrives with the first statement, mapped like a failed connection on any driver.
        $this->expectException(StatementException::class);

        $driver->query('SELECT 1');
    }
}
