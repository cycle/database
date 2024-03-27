<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\Postgres\DsnConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use PHPUnit\Framework\TestCase;

final class PostgresDriverConfigTest extends TestCase
{
    public function testSetState(): void
    {
        $config = new PostgresDriverConfig(
            connection: new DsnConnectionConfig($dsn = 'dsn'),
            schema: $schema = PostgresDriverConfig::DEFAULT_SCHEMA,
            driver: $driver = PostgresDriver::class,
            reconnect: $reconnect = true,
            timezone: $timezone = 'UTC',
            queryCache: $queryCache = true,
            readonlySchema: $readonlySchema = false,
            readonly: $readonly = false,
        );

        $exported = var_export($config, true);

        /** @var PostgresDriverConfig $recoveredConfig */
        eval('$recoveredConfig = '.$exported.';');

        $this->assertInstanceOf(DsnConnectionConfig::class, $recoveredConfig->connection);
        $this->assertSame("pgsql:$dsn", $recoveredConfig->connection->getDsn());
        $this->assertIsArray($recoveredConfig->schema);
        $this->assertCount(1, $recoveredConfig->schema);
        $this->assertSame($schema, $recoveredConfig->schema[0]);
        $this->assertSame($driver, $recoveredConfig->driver);
        $this->assertSame($reconnect, $recoveredConfig->reconnect);
        $this->assertSame($timezone, $recoveredConfig->timezone);
        $this->assertSame($queryCache, $recoveredConfig->queryCache);
        $this->assertSame($readonlySchema, $recoveredConfig->readonlySchema);
        $this->assertSame($readonly, $recoveredConfig->readonly);
    }
}
