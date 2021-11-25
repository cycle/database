<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\SQLite\SQLiteCompiler;
use Cycle\Database\Driver\SQLite\SQLiteHandler;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Throwable;

class AbstractDriverTest extends TestCase
{
    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = TestDriver::create(new SQLiteDriverConfig());
    }

    public function testLoggerShouldBeSet()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')->with("Insert ID: 0", []);

        $this->driver->setLogger($logger);
        $this->driver->lastInsertID();
    }
}
