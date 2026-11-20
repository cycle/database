<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Connection;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Connection\TransactionDisconnectingTest as CommonClass;
use Cycle\Database\Tests\Stub\Driver\SQLiteWrapDriver;

#[\Testo\Filter\Group('driver', 'driver-sqlite')]
class TransactionDisconnectingTest extends CommonClass
{
    public const DRIVER = 'sqlite-mock';

    /**
     * There is different behavior because on disconnect the DB file will be deleted.
     */
    #[\Testo\Test]
    public function testReconnectionOnBeginTransaction(): void
    {
        $driver = $this->database->getDriver();
        \assert($driver instanceof SQLiteWrapDriver);

        // Without transaction commit try to begin inner transaction and imitate reconnect
        $driver->exceptionOnTransactionBegin = 1;
        $driver->beginTransaction();
        // Transaction level is 1
        \Testo\Assert::same($driver->disconnectCalls, 1);
        \Testo\Assert::same($driver->getTransactionLevel(), 1);

        $this->database->commit();
        \Testo\Assert::same($driver->getTransactionLevel(), 0);
    }
}
