<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Query\Tokens;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\InsertQuery;
use PHPUnit\Framework\TestCase;

class InsertQueryTest
{
    #[\Testo\Test]
    public function testBuildQuery(): void
    {
        $insert = new InsertQuery();
        $insert
            ->into('table')
            ->columns('name', 'value')
            ->values(['Antony', 1]);

        \Testo\Assert::same($insert->getType(), CompilerInterface::INSERT_QUERY);

        \Testo\Assert::equals($insert->getTokens(), [
            'table' => 'table',
            'columns' => ['name', 'value'],
            'values' => [new Parameter(['Antony', 1])],
            'onConflict' => null,
        ]);
    }
}
