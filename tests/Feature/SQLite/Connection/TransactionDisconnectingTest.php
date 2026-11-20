<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLite\Connection;

use Cycle\Database\Tests\Feature\Common\Connection\TransactionDisconnectingTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Cycle\Testing\Database\Mock\SQLiteWrapDriver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::SQLite)]
final class TransactionDisconnectingTest extends CommonClass
{
    /**
     * There is different behavior because on disconnect the DB file will be deleted.
     */
    #[Test]
    public function testReconnectionOnBeginTransaction(): void
    {
        $driver = $this->database->getDriver();
        \assert($driver instanceof SQLiteWrapDriver);

        // Without transaction commit try to begin inner transaction and imitate reconnect
        $driver->exceptionOnTransactionBegin = 1;
        $driver->beginTransaction();
        // Transaction level is 1
        Assert::same($driver->disconnectCalls, 1);
        Assert::same($driver->getTransactionLevel(), 1);

        $this->database->commit();
        Assert::same($driver->getTransactionLevel(), 0);
    }
}
