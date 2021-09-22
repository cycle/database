<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\Oracle;

use Cycle\Database\Driver\Oracle\OracleDriver;
use PHPUnit\Framework\TestCase;

class DriverTest extends TestCase
{
    public function testIfSchemaOptionsDoesNotPresentUsePublicSchema(): void
    {
        $driver = new OracleDriver([
            'connection' => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username'   => 'oracle',
            'password'   => 'oracle',
            'schema' => ['$user', 'public']
        ]);

        $driver->connect();

        $this->assertSame(['oracle', 'public'], $driver->getSearchSchemas());
        $this->assertSame('"$user", public', $driver->query('SHOW search_path')->fetch()['search_path']);
    }

    public function testDefaultSchemaCanBeDefined(): void
    {
        $driver = new OracleDriver([
            'connection'     => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username'       => 'oracle',
            'password'       => 'oracle',
            'default_schema' => 'private'
        ]);

        $driver->connect();

        $this->assertSame(['private'], $driver->getSearchSchemas());
        $this->assertSame('private', $driver->query('SHOW search_path')->fetch()['search_path']);
    }

    public function testDefaultSchemaCanBeDefinedFromAvailableSchemas(): void
    {
        $driver = new OracleDriver([
            'connection' => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username'   => 'oracle',
            'password'   => 'oracle',
            'schema'     => 'private'
        ]);

        $driver->connect();

        $this->assertSame(['private'], $driver->getSearchSchemas());
        $this->assertSame('private', $driver->query('SHOW search_path')->fetch()['search_path']);
    }

    public function testDefaultSchemaCanNotBeRedefinedFromAvailableSchemas(): void
    {
        $driver = new OracleDriver([
            'connection'     => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username'       => 'oracle',
            'password'       => 'oracle',
            'default_schema' => 'private',
            'schema'         => ['test', 'private']
        ]);

        $driver->connect();

        $this->assertSame(['private', 'test'], $driver->getSearchSchemas());
        $this->assertSame('private, test', $driver->query('SHOW search_path')->fetch()['search_path']);
    }

    public function testDefaultSchemaForCurrentUser(): void
    {
        $driver = new OracleDriver([
            'connection'     => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username'       => 'oracle',
            'password'       => 'oracle',
            'default_schema' => '$user',
            'schema'         => ['test', 'private']
        ]);

        $driver->connect();

        $this->assertSame(['oracle', 'test', 'private'], $driver->getSearchSchemas());
        $this->assertSame('"$user", test, private', $driver->query('SHOW search_path')->fetch()['search_path']);
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testIfSchemaOptionsPresentsUseIt($schema, $available, $result): void
    {
        $driver = new OracleDriver([
            'connection' => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username'   => 'oracle',
            'password'   => 'oracle',
            'schema'     => $schema
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
            [['$user', 'schema2'], ['oracle', 'schema2'], '"$user", schema2']
        ];
    }
}
