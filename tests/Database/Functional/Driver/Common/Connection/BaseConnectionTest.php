<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Connection;

use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;
use Cycle\Database\Tests\Stub\Driver\MSSQLWrapDriver;
use Cycle\Database\Tests\Stub\Driver\MysqlWrapDriver;
use Cycle\Database\Tests\Stub\Driver\PostgresWrapDriver;
use Cycle\Database\Tests\Stub\Driver\SQLiteWrapDriver;

abstract class BaseConnectionTest extends BaseTest
{
    public function setUp(): void
    {
        parent::setUp();

        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();
    }

    public function tearDown(): void
    {
        $driver = $this->getDriver();
        $driver->setDefaults();
        parent::tearDown();
    }

    protected function getDriver(): SQLiteWrapDriver|MysqlWrapDriver|PostgresWrapDriver|MSSQLWrapDriver
    {
        $driver = $this->database->getDriver();
        \assert(
            $driver instanceof SQLiteWrapDriver
            || $driver instanceof MysqlWrapDriver
            || $driver instanceof PostgresWrapDriver
            || $driver instanceof MSSQLWrapDriver,
        );
        return $driver;
    }
}
