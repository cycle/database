<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\Postgres;

use Cycle\Database\Database;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Database\Driver\Postgres\Schema\PostgresTable;
use Cycle\Database\Schema\AbstractTable;

trait Helpers
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->dropAllTables();
    }

    protected function dropAllTables(): void
    {
        $driver = $this->getDriver();
        $schemas = ['public', 'schema1', 'schema2'];
        foreach ($schemas as $schema) {
            if ($driver->query("SELECT schema_name FROM information_schema.schemata WHERE schema_name = '{$schema}'")->fetch()) {
                $driver->query("DROP SCHEMA {$schema} CASCADE");
            }

            $driver->query("CREATE SCHEMA {$schema}");
        }
    }

    private function createTable(DriverInterface $driver, string $name): PostgresTable
    {
        $db = new Database('default', '', $driver);

        $schema = $db->table($name)->getSchema();

        $schema->primary('id');
        $schema->save();

        return $schema;
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

    /**
     * @param AbstractTable $table
     * @return AbstractTable
     */
    protected function fetchSchema(AbstractTable $table): AbstractTable
    {
        return $this->schema($table->getFullName());
    }
}
