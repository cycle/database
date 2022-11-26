<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\SQLite\DsnConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Config\SQLServerDriverConfig;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Driver\SQLServer\SQLServerDriver;
use PHPUnit\Framework\TestCase;

final class SQLiteDriverConfigTest extends TestCase
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

        $exported = var_export($config, true);

        /** @var SQLiteDriverConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        $this->assertInstanceOf(DsnConnectionConfig::class, $recoveredConfig->connection);
        $this->assertSame("sqlite:$dsn", $recoveredConfig->connection->getDsn());
        $this->assertSame($driver, $recoveredConfig->driver);
        $this->assertSame($reconnect, $recoveredConfig->reconnect);
        $this->assertSame($timezone, $recoveredConfig->timezone);
        $this->assertSame($queryCache, $recoveredConfig->queryCache);
        $this->assertSame($readonlySchema, $recoveredConfig->readonlySchema);
        $this->assertSame($readonly, $recoveredConfig->readonly);
    }
}
