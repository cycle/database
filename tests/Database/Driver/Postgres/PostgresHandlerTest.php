<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\Postgres;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use PHPUnit\Framework\TestCase;

class PostgresHandlerTest extends TestCase
{
    public function testGetsTableNamesWithoutSchema(): void
    {
        $driver = $this->getDriver();
        $tables = $this->createTables($driver);

        $this->assertTrue($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        $this->assertTrue($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        $this->assertFalse($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        $this->assertFalse($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_sh2']));

        $this->assertSame([$tables['test_pb']], $driver->getSchemaHandler()->getTableNames());
    }

    public function testGetsTableNamesWithSpecifiedSchemaAsString(): void
    {
        $driver = $this->getDriver('schema1');
        $tables = $this->createTables($driver);

        $this->assertFalse($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        $this->assertTrue($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        $this->assertFalse($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_sh2']));


        $this->assertSame([$tables['test_sh1']], $driver->getSchemaHandler()->getTableNames());
    }

    public function testGetsTableNamesWithSpecifiedSchemaAsArray(): void
    {
        $driver = $this->getDriver(['schema1', 'schema2']);
        $tables = $this->createTables($driver);

        $this->assertFalse($driver->getSchemaHandler()->hasTable('public.' . $tables['test_pb']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_pb']));

        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema1.' . $tables['test_sh1']));
        $this->assertTrue($driver->getSchemaHandler()->hasTable($tables['test_sh1']));

        $this->assertTrue($driver->getSchemaHandler()->hasTable('schema2.' . $tables['test_sh2']));
        $this->assertFalse($driver->getSchemaHandler()->hasTable($tables['test_sh2']));

        $this->assertSame([$tables['test_sh1'], $tables['test_sh2']], $driver->getSchemaHandler()->getTableNames());
    }

    protected function createTables(DriverInterface $driver): array
    {
        $this->dropAllTables($driver);

        $tables = [];
        $time = time();
        foreach (['public.test_pb', 'schema1.test_sh1', 'schema2.test_sh2'] as $table) {
            $driver->query('CREATE TABLE ' . $table . '_' . $time . '()');

            $table = explode('.', $table)[1];
            $tables[$table] = $table . '_' . $time;
        }

        return $tables;
    }

    protected function dropAllTables(DriverInterface $driver): void
    {
        $schemas = ['public', 'schema1', 'schema2'];
        foreach ($schemas as $schema) {
            if ($driver->query("SELECT schema_name FROM information_schema.schemata WHERE schema_name = '{$schema}'")->fetch()) {
                $driver->query("DROP SCHEMA {$schema} CASCADE");
            }

            $driver->query("CREATE SCHEMA {$schema}");
        }
    }

    private function getDriver($schema = null): DriverInterface
    {
        $options = [
            'connection' => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
            'username' => 'postgres',
            'password' => 'postgres'
        ];

        if ($schema) {
            $options['schema'] = $schema;
        }

        $driver = new PostgresDriver($options);
        $driver->connect();

        return $driver;
    }
}
