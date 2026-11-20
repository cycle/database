<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Driver\Statement;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

#[Test]
final class StatementTest
{
    public function closesTheCursorOnceTheRowsAreIterated(): void
    {
        $pdoStatement = m::mock(\PDOStatement::class);
        $pdoStatement->shouldReceive('setFetchMode')->once()->with(\PDO::FETCH_ASSOC);
        $pdoStatement->shouldReceive('getIterator')->andReturn(new \ArrayIterator([['id' => 1], ['id' => 2]]));
        $pdoStatement->shouldReceive('closeCursor')->once()->andReturn(true);

        $rows = \iterator_to_array(new Statement($pdoStatement));

        Assert::same($rows, [['id' => 1], ['id' => 2]]);
    }
}
