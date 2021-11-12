<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Query\Tokens;

use PHPUnit\Framework\TestCase;
use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\InsertQuery;

class InsertQueryTest extends TestCase
{
    public function testBuildQuery(): void
    {
        $insert = new InsertQuery();
        $insert
            ->into('table')
            ->columns('name', 'value')
            ->values(['Antony', 1]);

        $this->assertSame(
            CompilerInterface::INSERT_QUERY,
            $insert->getType()
        );

        $this->assertEquals(
            [
                'table' => 'table',
                'columns' => ['name', 'value'],
                'values' => [new Parameter(['Antony', 1])],
            ],
            $insert->getTokens()
        );
    }
}
