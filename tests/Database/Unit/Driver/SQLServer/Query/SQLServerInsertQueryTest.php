<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\SQLServer\Query;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\SQLServer\Query\SQLServerInsertQuery;
use Cycle\Database\Exception\BuilderException;
use PHPUnit\Framework\TestCase;

final class SQLServerInsertQueryTest
{
    #[\Testo\Test]
    public function testWithDriverException(): void
    {
        $insert = new SQLServerInsertQuery();

        \Testo\Expect::exception(BuilderException::class);
        $insert->withDriver(\Mockery::mock(DriverInterface::class)->shouldIgnoreMissing());
    }
}
