<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\Postgres;

use Cycle\Database\Driver\Postgres\PostgresDriver;
use PHPUnit\Framework\TestCase;

class DriverTest extends TestCase
{
    public function testIfSchemaOptionsDoesNotPresentUsePublicSchema(): void
    {
        $driver = new PostgresDriver([
            'connection' => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username' => 'postgres',
            'password' => 'postgres',
            'schema' => ['$user', 'public'],
        ]);

        $driver->connect();

        $this->assertSame(['postgres', 'public'], $driver->getSearchSchemas());
        $this->assertSame('"$user", public', $driver->query('SHOW search_path')->fetch()['search_path']);
    }

    public function testDefaultSchemaCanBeDefined(): void
    {
        $driver = new PostgresDriver([
            'connection' => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username' => 'postgres',
            'password' => 'postgres',
            'default_schema' => 'private',
        ]);

        $driver->connect();

        $this->assertSame(['private'], $driver->getSearchSchemas());
        $this->assertSame('private', $driver->query('SHOW search_path')->fetch()['search_path']);
    }

    public function testDefaultSchemaCanBeDefinedFromAvailableSchemas(): void
    {
        $driver = new PostgresDriver([
            'connection' => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username' => 'postgres',
            'password' => 'postgres',
            'schema' => 'private',
        ]);

        $driver->connect();

        $this->assertSame(['private'], $driver->getSearchSchemas());
        $this->assertSame('private', $driver->query('SHOW search_path')->fetch()['search_path']);
    }

    public function testDefaultSchemaCanNotBeRedefinedFromAvailableSchemas(): void
    {
        $driver = new PostgresDriver([
            'connection' => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username' => 'postgres',
            'password' => 'postgres',
            'default_schema' => 'private',
            'schema' => ['test', 'private'],
        ]);

        $driver->connect();

        $this->assertSame(['private', 'test'], $driver->getSearchSchemas());
        $this->assertSame('private, test', $driver->query('SHOW search_path')->fetch()['search_path']);
    }

    public function testDefaultSchemaForCurrentUser(): void
    {
        $driver = new PostgresDriver([
            'connection' => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username' => 'postgres',
            'password' => 'postgres',
            'default_schema' => '$user',
            'schema' => ['test', 'private'],
        ]);

        $driver->connect();

        $this->assertSame(['postgres', 'test', 'private'], $driver->getSearchSchemas());
        $this->assertSame('"$user", test, private', $driver->query('SHOW search_path')->fetch()['search_path']);
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testIfSchemaOptionsPresentsUseIt($schema, $available, $result): void
    {
        $driver = new PostgresDriver([
            'connection' => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username' => 'postgres',
            'password' => 'postgres',
            'schema' => $schema,
        ]);

        $this->assertSame($available, $driver->getSearchSchemas());
        $driver->connect();
        $this->assertSame($result, $driver->query('SHOW search_path')->fetch()['search_path']);
    }

    public function schemaProvider()
    {
        return [
            ['private', ['private'], 'private'],
            [['schema1', 'schema2'], ['schema1', 'schema2'], 'schema1, schema2'],
            [['$user', 'schema2'], ['postgres', 'schema2'], '"$user", schema2'],
        ];
    }
}
