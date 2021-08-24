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

        $driver->connect();

        $this->assertSame(['public'], $driver->getTableSchema());
        $this->assertSame('"$user", public', $driver->query('SHOW search_path')->fetch()['search_path']);
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testIfSchemaOptionsPresentsUseIt($schema, $result): void
    {
        $driver = new PostgresDriver([
            'connection' => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username'   => 'postgres',
            'password'   => 'postgres',
            'schema'     => $schema
        ]);

        $this->assertSame((array)$schema, $driver->getTableSchema());

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
