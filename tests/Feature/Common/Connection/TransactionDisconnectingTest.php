<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Connection;

use Cycle\Database\Exception\StatementException\ConnectionException;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\Driver;
use Cycle\Testing\Database\Mock\WrapDriverInterface;
use Testo\Assert;
use Testo\Test;

#[ExceptDriver(Driver::SQLite)]
class TransactionDisconnectingTest extends BaseConnectionTest
{
    #[Test]
    public function testReconnectionOnBeginTransaction(): void
    {
        $driver = $this->wrapDriver();

        // Without transaction commit try to begin inner transaction and imitate reconnect
        $driver->exceptionOnTransactionBegin = 1;
        $driver->beginTransaction();
        // Driver has reconnected
        Assert::same($driver->disconnectCalls, 1);
        // Transaction level is 1
        Assert::same($driver->getTransactionLevel(), 1);
        // There should be 0 saved records because previous transaction has been broken
        Assert::same($this->database->table->count(), 0);
        $this->database->table->insertOne(['name' => 'Anton2', 'value' => 234]);
        Assert::same($driver->getTransactionLevel(), 1);

        $this->database->commit();
        Assert::same($driver->getTransactionLevel(), 0);
    }

    #[Test]
    public function testFailedReconnectionOnBeginTransactionLeavesNoTransaction(): void
    {
        $driver = $this->wrapDriver();
        $driver->exceptionOnTransactionBegin = 2;

        try {
            $driver->beginTransaction();
            Assert::fail('The second failure has to reach the caller.');
        } catch (ConnectionException) {
        }

        Assert::same($driver->disconnectCalls, 1);
        Assert::same($driver->getTransactionLevel(), 0);
    }

    #[Test]
    public function testBeginTransactionWithoutReconnectFailsAtOnce(): void
    {
        $driver = $this->db(driverConfig: ['reconnect' => false])->getDriver();
        \assert($driver instanceof WrapDriverInterface);
        $driver->exceptionOnTransactionBegin = 1;

        try {
            $driver->beginTransaction();
            Assert::fail('The failure has to reach the caller.');
        } catch (ConnectionException) {
        }

        Assert::same($driver->disconnectCalls, 0);
        Assert::same($driver->getTransactionLevel(), 0);
    }
}
