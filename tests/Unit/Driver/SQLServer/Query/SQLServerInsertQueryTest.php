<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\SQLServer\Query;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\SQLServer\Query\SQLServerInsertQuery;
use Cycle\Database\Exception\BuilderException;
use Testo\Expect;
use Testo\Test;

#[Test]
final class SQLServerInsertQueryTest
{
    public function testWithDriverException(): void
    {
        $insert = new SQLServerInsertQuery();

        Expect::exception(BuilderException::class);
        $insert->withDriver(\Mockery::mock(DriverInterface::class)->shouldIgnoreMissing());
    }
}
