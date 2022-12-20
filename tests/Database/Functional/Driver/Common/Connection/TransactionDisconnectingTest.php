<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Connection;

abstract class TransactionDisconnectingTest extends BaseConnectionTest
{
    public function testReconnectionOnBeginTransaction(): void
    {
        $driver = $this->getDriver();

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
