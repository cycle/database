<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\MySQL\DsnConnectionConfig;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\Driver\MySQL\MySQLDriver;
use PHPUnit\Framework\TestCase;

final class MySQLDriverConfigTest
{
    #[\Testo\Test]
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

        \Testo\Assert::instanceOf($recoveredConfig->connection, DsnConnectionConfig::class);
        \Testo\Assert::same($recoveredConfig->connection->getDsn(), "mysql:$dsn");
        \Testo\Assert::same($recoveredConfig->driver, $driver);
        \Testo\Assert::same($recoveredConfig->reconnect, $reconnect);
        \Testo\Assert::same($recoveredConfig->timezone, $timezone);
        \Testo\Assert::same($recoveredConfig->queryCache, $queryCache);
        \Testo\Assert::same($recoveredConfig->readonlySchema, $readonlySchema);
        \Testo\Assert::same($recoveredConfig->readonly, $readonly);
    }
}
