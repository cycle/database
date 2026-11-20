<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Driver;

use Cycle\Database\Config\ConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

final class SearchPathTest extends DatabaseTestCase
{
    #[Test]
    public function testIfSchemaOptionsDoesNotPresentUsePublicSchema(): void
    {
        $driver = PostgresDriver::create(
            new PostgresDriverConfig(
                connection: $this->getConnection(),
                schema: ['$user', 'public'],
            ),
        );

        $driver->connect();

        Assert::same($driver->getSearchSchemas(), ['postgres', 'public']);
        Assert::same($driver->query('SHOW search_path')->fetch()['search_path'], '"$user", public');
    }

    #[Test]
    public function testDefaultSchemaCanBeDefined(): void
    {
        $driver = PostgresDriver::create(
            new PostgresDriverConfig(
                connection: $this->getConnection(),
                schema: 'private',
            ),
        );

        $driver->connect();

        Assert::same($driver->getSearchSchemas(), ['private']);
        Assert::same($driver->query('SHOW search_path')->fetch()['search_path'], 'private');
    }

    #[Test]
    public function testDefaultSchemaCanBeDefinedFromAvailableSchemas(): void
    {
        $driver = PostgresDriver::create(
            new PostgresDriverConfig(
                connection: $this->getConnection(),
                schema: 'private',
            ),
        );

        $driver->connect();

        Assert::same($driver->getSearchSchemas(), ['private']);
        Assert::same($driver->query('SHOW search_path')->fetch()['search_path'], 'private');
    }

    #[Test]
    public function testDefaultSchemaForCurrentUser(): void
    {
        $driver = PostgresDriver::create(
            new PostgresDriverConfig(
                connection: $this->getConnection(),
                schema: ['$user', 'test', 'private'],
            ),
        );

        $driver->connect();

        Assert::same($driver->getSearchSchemas(), ['postgres', 'test', 'private']);
        Assert::same($driver->query('SHOW search_path')->fetch()['search_path'], '"$user", test, private');
    }

    /**
     * @param mixed $schema
     * @param mixed $available
     * @param mixed $result
     */
    #[Test]
    #[DataProvider('schemaProvider')]
    public function testIfSchemaOptionsPresentsUseIt($schema, $available, $result): void
    {
        $driver = PostgresDriver::create(
            new PostgresDriverConfig(
                connection: $this->getConnection(),
                schema: $schema,
            ),
        );

        Assert::same($driver->getSearchSchemas(), $available);
        $driver->connect();
        Assert::same($driver->query('SHOW search_path')->fetch()['search_path'], $result);
    }

    public function schemaProvider()
    {
        return [
            ['private', ['private'], 'private'],
            [['schema1', 'schema2'], ['schema1', 'schema2'], 'schema1, schema2'],
            [['$user', 'schema2'], ['postgres', 'schema2'], '"$user", schema2'],
        ];
    }

    protected function getConnection(): ConnectionConfig
    {
        return $this->driver->config($this->context->pool->env)->connection;
    }
}
