<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\SQLite\DsnConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SQLiteDriverConfigTest
{
    public function testSetState(): void
    {
        $config = new SQLiteDriverConfig(
            connection: new DsnConnectionConfig($dsn = 'dsn'),
            driver: $driver = SQLiteDriver::class,
            reconnect: $reconnect = true,
            timezone: $timezone = 'UTC',
            queryCache: $queryCache = true,
            readonlySchema: $readonlySchema = false,
            readonly: $readonly = false,
        );

        $exported = \var_export($config, true);

        /** @var SQLiteDriverConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        Assert::instanceOf($recoveredConfig->connection, DsnConnectionConfig::class);
        Assert::same($recoveredConfig->connection->getDsn(), "sqlite:$dsn");
        Assert::same($recoveredConfig->driver, $driver);
        Assert::same($recoveredConfig->reconnect, $reconnect);
        Assert::same($recoveredConfig->timezone, $timezone);
        Assert::same($recoveredConfig->queryCache, $queryCache);
        Assert::same($recoveredConfig->readonlySchema, $readonlySchema);
        Assert::same($recoveredConfig->readonly, $readonly);
    }
}
