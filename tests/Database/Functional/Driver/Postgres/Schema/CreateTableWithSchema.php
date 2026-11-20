<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

use Cycle\Database\Exception\DriverException;
use Cycle\Database\Tests\Functional\Driver\Postgres\Helpers;
use PHPUnit\Framework\TestCase;

class CreateTableWithSchema
{
    use Helpers;

    #[\Testo\Test]
    public function testCreatesWithPublicSchema(): void
    {
        $driver = $this->getDriver(null, '$user');
        $this->createTable($driver, 'public.test');
        $this->createTable($driver, 'test');
        $this->createTable($driver, 'schema1.test');

        \Testo\Assert::same($driver->getSchemaHandler()->getTableNames(), [
            'public.test', 'postgres.test', 'schema1.test',
        ]);
    }

    #[\Testo\Test]
    public function testCreatesWithSingleSchema(): void
    {
        $driver = $this->getDriver('schema1');

        $this->createTable($driver, 'test');
        $this->createTable($driver, 'schema1.test1');

        \Testo\Assert::same($driver->getSchemaHandler()->getTableNames(), [
            'schema1.test',
            'schema1.test1',
        ]);
    }

    #[\Testo\Test]
    public function testCreatesWithMultipleSchema(): void
    {
        $driver = $this->getDriver(['schema2', 'schema1']);
        $this->createTable($driver, 'test');
        $this->createTable($driver, 'schema1.test1');
        $this->createTable($driver, 'schema2.test2');

        \Testo\Assert::same($driver->getSchemaHandler()->getTableNames(), [
            'schema2.test',
            'schema1.test1',
            'schema2.test2',
        ]);
    }

    #[\Testo\Test]
    public function testCreatesTableForNotDefinedSchemaShouldThrowAnException(): void
    {
        \Testo\Expect::exception(DriverException::class)->withMessageContaining('Schema `schema3` has not been defined.');
        $driver = $this->getDriver(['schema2', 'schema1']);
        $this->createTable($driver, 'schema3.test1');
    }

    #[\Testo\Lifecycle\BeforeTest]
    protected function setUp(): void
    {
        $this->setUpSchemas();
    }

    #[\Testo\Lifecycle\AfterTest]
    protected function tearDown(): void
    {
        $this->dropUserSchema();
    }
}
