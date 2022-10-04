<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Connection;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Connection\TransactionDisconnectingTest as CommonClass;
use Cycle\Database\Tests\Stub\Driver\SQLiteWrapDriver;

/**
 * @group driver
 * @group driver-sqlite
 */
class TransactionDisconnectingTest extends CommonClass
{
    public const DRIVER = 'sqlite-mock';

    /**
     * There is different behavior because on disconnect the DB file will be deleted.
     */
    public function testReconnectionOnBeginTransaction(): void
    {
        $driver = $this->database->getDriver();
        \assert($driver instanceof SQLiteWrapDriver);

        // Without transaction commit try to begin inner transaction and imitate reconnect
        $driver->exceptionOnTransactionBegin = 1;
        $driver->beginTransaction();
        // Transaction level is 1
        $this->assertSame(1, $driver->disconnectCalls);
        $this->assertSame(1, $driver->getTransactionLevel());

        $this->database->commit();
        $this->assertSame(0, $driver->getTransactionLevel());
    }
}
