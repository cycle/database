<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\Postgres;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\Postgres\PostgresHandler;
use PHPUnit\Framework\TestCase;

final class PostgresHandlerTest extends TestCase
{
    public function testEnableForeignKeyConstraints(): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $driver
            ->expects($this->once())
            ->method('execute')
            ->with('SET CONSTRAINTS ALL IMMEDIATE;');

        $handler = (new PostgresHandler())->withDriver($driver);

        $handler->enableForeignKeyConstraints();
    }

    public function testDisableForeignKeyConstraints(): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $driver
            ->expects($this->once())
            ->method('execute')
            ->with('SET CONSTRAINTS ALL DEFERRED;');

        $handler = (new PostgresHandler())->withDriver($driver);

        $handler->disableForeignKeyConstraints();
    }
}
