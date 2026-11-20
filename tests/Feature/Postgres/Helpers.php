<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres;

use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\Database;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Database\Driver\Postgres\Schema\PostgresTable;
use Testo\Lifecycle\BeforeTest;

/**
 * Recreates the schemas `public`, `schema1`, `schema2` and `postgres` before each test and builds drivers
 * with their own search path.
 */
trait Helpers
{
    #[BeforeTest]
    public function setUpSchemas(): void
    {
        $driver = $this->getDriver();
        $schemas = ['public', 'schema1', 'schema2', 'postgres'];
        foreach ($schemas as $schema) {
            $query = "SELECT schema_name
                        FROM information_schema.schemata
                        WHERE schema_name = '{$schema}'";
            if ($driver->query($query)->fetch()) {
                $driver->query("DROP SCHEMA {$schema} CASCADE");
            }

            $driver->query("CREATE SCHEMA {$schema}");
        }
    }

    protected function dropUserSchema(): void
    {
        $driver = $this->getDriver();
        $query = "SELECT schema_name FROM information_schema.schemata WHERE schema_name = 'postgres'";
        if ($driver->query($query)->fetch()) {
            $driver->query('DROP SCHEMA postgres CASCADE');
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

    /**
     * A new driver on the suite's server, so schema caches never outlive the schemas dropped between tests.
     * @param null|mixed $schema
     */
    private function getDriver($schema = null, ?string $defaultSchema = null): DriverInterface
    {
        $config = $this->driver->config($this->context->pool->env);
        \assert($config instanceof PostgresDriverConfig);
        $config->schema = \array_values(\array_unique(
            \array_filter([$defaultSchema, ...\array_values((array) $schema)]),
        )) ?: [PostgresDriverConfig::DEFAULT_SCHEMA];

        $driver = PostgresDriver::create($config);
        $driver->connect();

        return $driver;
    }
}
