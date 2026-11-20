<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\MySQL\DsnConnectionConfig;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\Driver\MySQL\MySQLDriver;
use Testo\Assert;
use Testo\Test;

#[Test]
final class MySQLDriverConfigTest
{
    public function testSetState(): void
    {
        $config = new MySQLDriverConfig(
            connection: new DsnConnectionConfig($dsn = 'dsn'),
            driver: $driver = MySQLDriver::class,
            reconnect: $reconnect = true,
            timezone: $timezone = 'UTC',
            queryCache: $queryCache = true,
            readonlySchema: $readonlySchema = false,
            readonly: $readonly = false,
        );

        $exported = \var_export($config, true);

        /** @var MySQLDriverConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        Assert::instanceOf($recoveredConfig->connection, DsnConnectionConfig::class);
        Assert::same($recoveredConfig->connection->getDsn(), "mysql:$dsn");
        Assert::same($recoveredConfig->driver, $driver);
        Assert::same($recoveredConfig->reconnect, $reconnect);
        Assert::same($recoveredConfig->timezone, $timezone);
        Assert::same($recoveredConfig->queryCache, $queryCache);
        Assert::same($recoveredConfig->readonlySchema, $readonlySchema);
        Assert::same($recoveredConfig->readonly, $readonly);
    }
}
