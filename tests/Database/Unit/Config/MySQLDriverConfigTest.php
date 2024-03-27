<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\MySQL\DsnConnectionConfig;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\Driver\MySQL\MySQLDriver;
use PHPUnit\Framework\TestCase;

final class MySQLDriverConfigTest extends TestCase
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

        $exported = var_export($config, true);

        /** @var MySQLDriverConfig $recoveredConfig */
        eval('$recoveredConfig = '.$exported.';');

        $this->assertInstanceOf(DsnConnectionConfig::class, $recoveredConfig->connection);
        $this->assertSame("mysql:$dsn", $recoveredConfig->connection->getDsn());
        $this->assertSame($driver, $recoveredConfig->driver);
        $this->assertSame($reconnect, $recoveredConfig->reconnect);
        $this->assertSame($timezone, $recoveredConfig->timezone);
        $this->assertSame($queryCache, $recoveredConfig->queryCache);
        $this->assertSame($readonlySchema, $recoveredConfig->readonlySchema);
        $this->assertSame($readonly, $recoveredConfig->readonly);
    }
}
