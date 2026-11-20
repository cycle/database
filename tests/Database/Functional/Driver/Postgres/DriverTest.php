<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres;

use Cycle\Database\Config\Postgres\TcpConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use PHPUnit\Framework\TestCase;

class DriverTest
{
    #[\Testo\Test]
    public function testIfSchemaOptionsDoesNotPresentUsePublicSchema(): void
    {
        $driver = PostgresDriver::create(
            new PostgresDriverConfig(
                connection: $this->getConnection(),
                schema: ['$user', 'public'],
            ),
        );

        $driver->connect();

        \Testo\Assert::same($driver->getSearchSchemas(), ['postgres', 'public']);
        \Testo\Assert::same($driver->query('SHOW search_path')->fetch()['search_path'], '"$user", public');
    }

    #[\Testo\Test]
    public function testDefaultSchemaCanBeDefined(): void
    {
        $driver = PostgresDriver::create(
            new PostgresDriverConfig(
                connection: $this->getConnection(),
                schema: 'private',
            ),
        );

        $driver->connect();

        \Testo\Assert::same($driver->getSearchSchemas(), ['private']);
        \Testo\Assert::same($driver->query('SHOW search_path')->fetch()['search_path'], 'private');
    }

    #[\Testo\Test]
    public function testDefaultSchemaCanBeDefinedFromAvailableSchemas(): void
    {
        $driver = PostgresDriver::create(
            new PostgresDriverConfig(
                connection: $this->getConnection(),
                schema: 'private',
            ),
        );

        $driver->connect();

        \Testo\Assert::same($driver->getSearchSchemas(), ['private']);
        \Testo\Assert::same($driver->query('SHOW search_path')->fetch()['search_path'], 'private');
    }

    #[\Testo\Test]
    public function testDefaultSchemaForCurrentUser(): void
    {
        $driver = PostgresDriver::create(
            new PostgresDriverConfig(
                connection: $this->getConnection(),
                schema: ['$user', 'test', 'private'],
            ),
        );

        $driver->connect();

        \Testo\Assert::same($driver->getSearchSchemas(), ['postgres', 'test', 'private']);
        \Testo\Assert::same($driver->query('SHOW search_path')->fetch()['search_path'], '"$user", test, private');
    }

    /**
     * @param mixed $schema
     * @param mixed $available
     * @param mixed $result
     */
    #[\Testo\Test]
    #[\Testo\Data\DataProvider('schemaProvider')]
    public function testIfSchemaOptionsPresentsUseIt($schema, $available, $result): void
    {
        $driver = PostgresDriver::create(
            new PostgresDriverConfig(
                connection: $this->getConnection(),
                schema: $schema,
            ),
        );

        \Testo\Assert::same($driver->getSearchSchemas(), $available);
        $driver->connect();
        \Testo\Assert::same($driver->query('SHOW search_path')->fetch()['search_path'], $result);
    }

    public function schemaProvider()
    {
        return [
            ['private', ['private'], 'private'],
            [['schema1', 'schema2'], ['schema1', 'schema2'], 'schema1, schema2'],
            [['$user', 'schema2'], ['postgres', 'schema2'], '"$user", schema2'],
        ];
    }

    /**
     * TODO Should be moved in common config
     *
     */
    protected function getConnection(): TcpConnectionConfig
    {
        return new TcpConnectionConfig(
            database: 'spiral',
            host: '127.0.0.1',
            port: 15432,
            user: 'postgres',
            password: 'YourStrong!Passw0rd',
        );
    }
}
