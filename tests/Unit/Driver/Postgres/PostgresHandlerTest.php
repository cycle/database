<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\Postgres;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\Postgres\PostgresHandler;
use Testo\Test;

#[Test]
final class PostgresHandlerTest
{
    public function testEnableForeignKeyConstraints(): void
    {
        $driver = \Mockery::mock(DriverInterface::class)->shouldIgnoreMissing();
        $driver->shouldReceive('execute')->once()->with('SET CONSTRAINTS ALL IMMEDIATE;', \Mockery::andAnyOtherArgs());

        $handler = (new PostgresHandler())->withDriver($driver);

        $handler->enableForeignKeyConstraints();
    }

    public function testDisableForeignKeyConstraints(): void
    {
        $driver = \Mockery::mock(DriverInterface::class)->shouldIgnoreMissing();
        $driver->shouldReceive('execute')->once()->with('SET CONSTRAINTS ALL DEFERRED;', \Mockery::andAnyOtherArgs());

        $handler = (new PostgresHandler())->withDriver($driver);

        $handler->disableForeignKeyConstraints();
    }
}
