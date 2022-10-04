<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Connection;

use Cycle\Database\Schema\AbstractTable;
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

        $driver = $this->getDriver();
        $driver->setDefaults();
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    protected function getDriver(): SQLiteWrapDriver|MysqlWrapDriver|PostgresWrapDriver|MSSQLWrapDriver
    {
        $driver = $this->database->getDriver();
        \assert(
            $driver instanceof SQLiteWrapDriver
            || $driver instanceof MysqlWrapDriver
            || $driver instanceof PostgresWrapDriver,
        );
        return $driver;
    }
}
