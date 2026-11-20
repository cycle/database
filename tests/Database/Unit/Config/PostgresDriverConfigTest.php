<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\Postgres\DsnConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use PHPUnit\Framework\TestCase;

final class PostgresDriverConfigTest
{
    #[\Testo\Test]
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

        \Testo\Assert::instanceOf($recoveredConfig->connection, DsnConnectionConfig::class);
        \Testo\Assert::same($recoveredConfig->connection->getDsn(), "pgsql:$dsn");
        \Testo\Assert::array($recoveredConfig->schema);
        \Testo\Assert::count($recoveredConfig->schema, 1);
        \Testo\Assert::same($recoveredConfig->schema[0], $schema);
        \Testo\Assert::same($recoveredConfig->driver, $driver);
        \Testo\Assert::same($recoveredConfig->reconnect, $reconnect);
        \Testo\Assert::same($recoveredConfig->timezone, $timezone);
        \Testo\Assert::same($recoveredConfig->queryCache, $queryCache);
        \Testo\Assert::same($recoveredConfig->readonlySchema, $readonlySchema);
        \Testo\Assert::same($recoveredConfig->readonly, $readonly);
    }
}
