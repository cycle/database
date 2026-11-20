<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\SQLServer;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\SQLite\Schema\SQLiteColumn;
use Cycle\Database\Driver\SQLServer\SQLServerHandler;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractTable;
use Testo\Expect;
use Testo\Test;

#[Test]
final class SQLServerHandlerTest
{
    public function testAlterColumnRejectsForeignColumns(): never
    {
        $handler = (new SQLServerHandler())->withDriver(\Mockery::mock(DriverInterface::class));

        Expect::exception(SchemaException::class)
            ->withMessage('SQlServer handler can work only with SQLServer columns');

        $handler->alterColumn(
            \Mockery::mock(AbstractTable::class),
            new SQLiteColumn('table', 'column'),
            new SQLiteColumn('table', 'column'),
        );
    }
}
