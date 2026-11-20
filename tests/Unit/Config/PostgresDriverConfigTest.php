<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\Postgres\DsnConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Testo\Assert;
use Testo\Test;

#[Test]
final class PostgresDriverConfigTest
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

        $exported = \var_export($config, true);

        /** @var PostgresDriverConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        Assert::instanceOf($recoveredConfig->connection, DsnConnectionConfig::class);
        Assert::same($recoveredConfig->connection->getDsn(), "pgsql:$dsn");
        Assert::array($recoveredConfig->schema);
        Assert::count($recoveredConfig->schema, 1);
        Assert::same($recoveredConfig->schema[0], $schema);
        Assert::same($recoveredConfig->driver, $driver);
        Assert::same($recoveredConfig->reconnect, $reconnect);
        Assert::same($recoveredConfig->timezone, $timezone);
        Assert::same($recoveredConfig->queryCache, $queryCache);
        Assert::same($recoveredConfig->readonlySchema, $readonlySchema);
        Assert::same($recoveredConfig->readonly, $readonly);
    }

    public function fallsBackToTheDefaultSchemaForAnEmptyList(): void
    {
        $config = new PostgresDriverConfig(new DsnConnectionConfig('dsn'), schema: []);

        Assert::same($config->schema, [PostgresDriverConfig::DEFAULT_SCHEMA]);
    }
}
