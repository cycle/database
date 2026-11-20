<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\Postgres;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\Postgres\PostgresHandler;
use Cycle\Database\Driver\SQLite\Schema\SQLiteColumn;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractTable;
use Testo\Expect;
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

    public function testAlterColumnRejectsForeignColumns(): never
    {
        $handler = (new PostgresHandler())->withDriver(\Mockery::mock(DriverInterface::class));

        Expect::exception(SchemaException::class)
            ->withMessage('Postgres handler can work only with Postgres columns');

        $handler->alterColumn(
            \Mockery::mock(AbstractTable::class),
            new SQLiteColumn('table', 'column'),
            new SQLiteColumn('table', 'column'),
        );
    }

    public function testCreateTableRejectsForeignTables(): never
    {
        $handler = (new PostgresHandler())->withDriver(\Mockery::mock(DriverInterface::class));

        Expect::exception(SchemaException::class)
            ->withMessage('Postgres handler can work only with Postgres table');

        $handler->createTable(\Mockery::mock(AbstractTable::class));
    }
}
