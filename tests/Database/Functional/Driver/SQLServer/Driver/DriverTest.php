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

    public function testCreateReportsAFailedConnectionAsALibraryException(): void
    {
        $config = clone self::$config[static::DRIVER];
        \assert($config instanceof DriverConfig);

        $connection = clone $config->connection;
        $connection->password = 'definitely not the password';
        $config->connection = $connection;

        // create() reaches the server to read its version, which makes it the one place a
        // connection failure happens outside Driver::statement() and so the one place that has to
        // classify the failure itself.
        $this->expectException(StatementException::class);

        SQLServerDriver::create($config);
    }
}
