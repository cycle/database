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
            'username'   => 'postgres',
            'password'   => 'postgres',
        ]);

        $this->assertSame(['public'], $driver->getTableSchema());
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testIfSchemaOptionsPresentsUseIt($schema): void
    {
        $driver = new PostgresDriver([
            'connection' => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username'   => 'postgres',
            'password'   => 'postgres',
            'schema'     => $schema
        ]);

        $this->assertSame((array)$schema, $driver->getTableSchema());
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testSchemaShouldBeAddToSearchPathAfterConnectIfItSet($schema, $result): void
    {
        $driver = new PostgresDriver([
            'connection' => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username'   => 'postgres',
            'password'   => 'postgres',
            'schema'     => $schema
        ]);

        $driver->connect();

        $this->assertSame($result, $driver->query('SHOW search_path')->fetch()['search_path']);
    }

    public function schemaProvider()
    {
        return [
            'string' => ['private', 'private'],
            'array' => [['schema1', 'schema2'], 'schema1, schema2']
        ];
    }
}
