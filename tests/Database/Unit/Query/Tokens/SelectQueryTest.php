<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Query\Tokens;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\SelectQuery;
use PHPUnit\Framework\TestCase;

class SelectQueryTest extends TestCase
{
    public function testBuildQuery(): void
    {
        $select = new SelectQuery();
        $select
            ->from('table')
            ->columns('name', 'value')
            ->where(['name' => 'Antony'])
            ->orWhere('id', '>', 1)
            ->orderBy('name', 'ASC')
            ->orderBy('id', 'DESC')
            ->orderBy('RAND()', null)
            ->orderBy([
                'FOO()' => null,
                'COALESCE(name, test)',
            ]);

        $this->assertSame(
            CompilerInterface::SELECT_QUERY,
            $select->getType(),
        );

        $this->assertEquals(
            [
                'forUpdate' => false,
                'from'      => ['table'],
                'join'      => [],
                'columns'   => ['name', 'value'],
                'distinct'  => false,
                'where'     => [
                    [
                        'AND',
                        ['name', '=', new Parameter('Antony')],
                    ],
                    [
                        'OR',
                        ['id', '>', new Parameter(1)],
                    ],
                ],
                'having'  => [],
                'groupBy' => [],
                'orderBy' => [
                    ['name', 'ASC'],
                    ['id', 'DESC'],
                    ['RAND()', null],
                    ['FOO()', null],
                    ['COALESCE(name, test)', null],
                ],
                'limit'  => null,
                'offset' => null,
                'union'  => [],
            ],
            $select->getTokens(),
        );
    }
}
