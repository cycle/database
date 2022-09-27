<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Query;

use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;
use Cycle\Database\Tests\Stub\Driver\MysqlWrapDriver;
use Cycle\Database\Tests\Stub\Driver\PostgresWrapDriver;
use Cycle\Database\Tests\Stub\Driver\SQLiteWrapDriver;

abstract class TransactionDisconnectingTest extends BaseTest
{
    public function setUp(): void
    {
        parent::setUp();

        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();

        $driver = $this->database->getDriver();
        \assert(
            $driver instanceof SQLiteWrapDriver
            || $driver instanceof MysqlWrapDriver
            || $driver instanceof PostgresWrapDriver
        );
        $driver->setDefaults();
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    public function testReconnectionOnBeginTransaction(): void
    {
        $driver = $this->database->getDriver();
        \assert(
            $driver instanceof SQLiteWrapDriver
            || $driver instanceof MysqlWrapDriver
            || $driver instanceof PostgresWrapDriver
        );

        // Without transaction commit try to begin inner transaction and imitate reconnect
        $driver->exceptionOnTransactionBegin = 1;
        $driver->beginTransaction();
        // Driver has reconnected
        $this->assertSame(1, $driver->disconnectCalls);
        // Transaction level is 1
        $this->assertSame(1, $driver->getTransactionLevel());
        // There should be 0 saved records because previous transaction has been broken
        $this->assertSame(0, $this->database->table->count());
        $this->database->table->insertOne(['name' => 'Anton2', 'value' => 234]);
        $this->assertSame(1, $driver->getTransactionLevel());

        $this->database->commit();
        $this->assertSame(0, $driver->getTransactionLevel());
    }
}
