<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Connection;

abstract class TransactionDisconnectingTest extends BaseConnectionTest
{
    #[\Testo\Test]
    public function testReconnectionOnBeginTransaction(): void
    {
        $driver = $this->getDriver();

        // Without transaction commit try to begin inner transaction and imitate reconnect
        $driver->exceptionOnTransactionBegin = 1;
        $driver->beginTransaction();
        // Driver has reconnected
        \Testo\Assert::same($driver->disconnectCalls, 1);
        // Transaction level is 1
        \Testo\Assert::same($driver->getTransactionLevel(), 1);
        // There should be 0 saved records because previous transaction has been broken
        \Testo\Assert::same($this->database->table->count(), 0);
        $this->database->table->insertOne(['name' => 'Anton2', 'value' => 234]);
        \Testo\Assert::same($driver->getTransactionLevel(), 1);

        $this->database->commit();
        \Testo\Assert::same($driver->getTransactionLevel(), 0);
    }
}
