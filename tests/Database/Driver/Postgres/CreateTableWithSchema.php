<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\Postgres;

use PHPUnit\Framework\TestCase;

class CreateTableWithSchema extends TestCase
{
    use Helpers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropAllTables();
    }

    public function testCreatesWithPublicSchema(): void
    {
        $driver = $this->getDriver();
        $this->createTable($driver, 'test');
        $this->createTable($driver, 'schema1.test');

        $this->assertSame([
            'public.test'
        ], $driver->getSchemaHandler()->getTableNames());
    }

    public function testCreatesWithSingleSchema(): void
    {
        $driver = $this->getDriver('schema1');
        $this->createTable($driver, 'test');
        $this->createTable($driver, 'schema1.test1');
        $this->createTable($driver, 'schema2.test2');

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
}
