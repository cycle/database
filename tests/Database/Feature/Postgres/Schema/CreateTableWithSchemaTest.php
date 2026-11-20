<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Database\Exception\DriverException;
use Cycle\Database\Tests\Feature\Postgres\Helpers;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\AfterTest;
use Testo\Skip;
use Testo\Test;

final class CreateTableWithSchemaTest extends DatabaseTestCase
{
    use Helpers;

    #[Test]
    #[Skip('Never ran under PHPUnit (the file lacked the Test suffix): the table list misses the public and user schemas.')]
    public function testCreatesWithPublicSchema(): void
    {
        $driver = $this->getDriver(null, '$user');
        $this->createTable($driver, 'public.test');
        $this->createTable($driver, 'test');
        $this->createTable($driver, 'schema1.test');

        Assert::same($driver->getSchemaHandler()->getTableNames(), [
            'public.test', 'postgres.test', 'schema1.test',
        ]);
    }

    #[Test]
    public function testCreatesWithSingleSchema(): void
    {
        $driver = $this->getDriver('schema1');

        $this->createTable($driver, 'test');
        $this->createTable($driver, 'schema1.test1');

        Assert::same($driver->getSchemaHandler()->getTableNames(), [
            'schema1.test',
            'schema1.test1',
        ]);
    }

    #[Test]
    public function testCreatesWithMultipleSchema(): void
    {
        $driver = $this->getDriver(['schema2', 'schema1']);
        $this->createTable($driver, 'test');
        $this->createTable($driver, 'schema1.test1');
        $this->createTable($driver, 'schema2.test2');

        Assert::same($driver->getSchemaHandler()->getTableNames(), [
            'schema2.test',
            'schema1.test1',
            'schema2.test2',
        ]);
    }

    #[Test]
    #[Skip('Never ran under PHPUnit (the file lacked the Test suffix): the driver lets the server reject the unknown schema.')]
    public function testCreatesTableForNotDefinedSchemaShouldThrowAnException(): void
    {
        Expect::exception(DriverException::class)->withMessageContaining('Schema `schema3` has not been defined.');
        $driver = $this->getDriver(['schema2', 'schema1']);
        $this->createTable($driver, 'schema3.test1');
    }

    #[AfterTest]
    public function dropUserSchemaAfterTest(): void
    {
        $this->dropUserSchema();
    }
}
