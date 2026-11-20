<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\SQLServer\DsnConnectionConfig;
use Cycle\Database\Config\SQLServerDriverConfig;
use Cycle\Database\Driver\SQLServer\SQLServerDriver;
use PHPUnit\Framework\TestCase;

final class SQLServerDriverConfigTest
{
    #[\Testo\Test]
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

        \Testo\Assert::instanceOf($recoveredConfig->connection, DsnConnectionConfig::class);
        \Testo\Assert::same($recoveredConfig->connection->getDsn(), "sqlsrv:$dsn");
        \Testo\Assert::same($recoveredConfig->driver, $driver);
        \Testo\Assert::same($recoveredConfig->reconnect, $reconnect);
        \Testo\Assert::same($recoveredConfig->timezone, $timezone);
        \Testo\Assert::same($recoveredConfig->queryCache, $queryCache);
        \Testo\Assert::same($recoveredConfig->readonlySchema, $readonlySchema);
        \Testo\Assert::same($recoveredConfig->readonly, $readonly);
    }
}
