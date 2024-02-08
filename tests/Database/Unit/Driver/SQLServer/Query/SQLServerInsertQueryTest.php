<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\SQLServer\Query;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\SQLServer\Query\SQLServerInsertQuery;
use Cycle\Database\Exception\BuilderException;
use PHPUnit\Framework\TestCase;

final class SQLServerInsertQueryTest extends TestCase
{
    public function testWithDriverException(): void
    {
        $insert = new SQLServerInsertQuery();

        $this->expectException(BuilderException::class);
        $insert->withDriver($this->createMock(DriverInterface::class));
    }
}
