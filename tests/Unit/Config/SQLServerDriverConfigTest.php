<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\SQLServer\DsnConnectionConfig;
use Cycle\Database\Config\SQLServerDriverConfig;
use Cycle\Database\Driver\SQLServer\SQLServerDriver;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SQLServerDriverConfigTest
{
    public function testSetState(): void
    {
        $config = new SQLServerDriverConfig(
            connection: new DsnConnectionConfig($dsn = 'dsn'),
            driver: $driver = SQLServerDriver::class,
            reconnect: $reconnect = true,
            timezone: $timezone = 'UTC',
            queryCache: $queryCache = true,
            readonlySchema: $readonlySchema = false,
            readonly: $readonly = false,
        );

        $exported = \var_export($config, true);

        /** @var SQLServerDriverConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        Assert::instanceOf($recoveredConfig->connection, DsnConnectionConfig::class);
        Assert::same($recoveredConfig->connection->getDsn(), "sqlsrv:$dsn");
        Assert::same($recoveredConfig->driver, $driver);
        Assert::same($recoveredConfig->reconnect, $reconnect);
        Assert::same($recoveredConfig->timezone, $timezone);
        Assert::same($recoveredConfig->queryCache, $queryCache);
        Assert::same($recoveredConfig->readonlySchema, $readonlySchema);
        Assert::same($recoveredConfig->readonly, $readonly);
    }
}
