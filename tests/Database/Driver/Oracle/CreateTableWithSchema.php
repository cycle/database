<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\Oracle;

use Cycle\Database\Exception\DriverException;
use PHPUnit\Framework\TestCase;

class CreateTableWithSchema extends TestCase
{
    use Helpers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSchemas();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->dropUserSchema();
    }

    public function testCreatesWithPublicSchema(): void
    {
        $driver = $this->getDriver(null, '$user');
        $this->createTable($driver, 'public.test');
        $this->createTable($driver, 'test');
        $this->createTable($driver, 'schema1.test');

        $this->assertSame([
            'public.test', 'oracle.test', 'schema1.test'
        ], $driver->getSchemaHandler()->getTableNames());
    }

    public function testCreatesWithSingleSchema(): void
    {
        $driver = $this->getDriver('schema1');

        $this->createTable($driver, 'test');
        $this->createTable($driver, 'schema1.test1');

        $this->assertSame([
            'schema1.test',
            'schema1.test1',
        ], $driver->getSchemaHandler()->getTableNames());
    }

    public function testCreatesWithMultipleSchema(): void
    {
        $driver = $this->getDriver(['schema2', 'schema1']);
        $this->createTable($driver, 'test');
        $this->createTable($driver, 'schema1.test1');
        $this->createTable($driver, 'schema2.test2');

        $this->assertSame([
            'schema2.test',
            'schema1.test1',
            'schema2.test2',
        ], $driver->getSchemaHandler()->getTableNames());
    }

    public function testCreatesTableForNotDefinedSchemaShouldThrowAnException(): void
    {
        $this->expectException(DriverException::class);
        $this->expectErrorMessage('Schema `schema3` has not been defined.');
        $driver = $this->getDriver(['schema2', 'schema1']);
        $this->createTable($driver, 'schema3.test1');
    }
}
