<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Query\Tokens;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\InsertQuery;
use Testo\Assert;
use Testo\Test;

#[Test]
final class InsertQueryTest
{
    public function testBuildQuery(): void
    {
        $insert = new InsertQuery();
        $insert
            ->into('table')
            ->columns('name', 'value')
            ->values(['Antony', 1]);

        Assert::same($insert->getType(), CompilerInterface::INSERT_QUERY);

        Assert::equals($insert->getTokens(), [
            'table' => 'table',
            'columns' => ['name', 'value'],
            'values' => [new Parameter(['Antony', 1])],
            'onConflict' => null,
        ]);
    }
}
