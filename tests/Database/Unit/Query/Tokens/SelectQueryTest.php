<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Query\Tokens;

use PHPUnit\Framework\TestCase;
use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\SelectQuery;

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
                'from' => ['table'],
                'join' => [],
                'columns' => ['name', 'value'],
                'distinct' => false,
                'where' => [
                    [
                        'AND',
                        ['name', '=', new Parameter('Antony')],
                    ],
                    [
                        'OR',
                        ['id', '>', new Parameter(1)],
                    ],
                ],
                'having' => [],
                'groupBy' => [],
                'orderBy' => [
                    ['name', 'ASC'],
                    ['id', 'DESC'],
                    ['RAND()', null],
                    ['FOO()', null],
                    ['COALESCE(name, test)', null],
                ],
                'limit' => null,
                'offset' => null,
                'union' => [],
                'intersect' => [],
                'except' => [],
            ],
            $select->getTokens(),
        );
    }

    public function testWrapWhereOnEmptyIsNoop(): void
    {
        $select = (new SelectQuery())->from('table');
        $select->wrapWhere();

        $this->assertSame([], $select->getTokens()['where']);
    }

    public function testWrapWhereEnclosesExistingTokens(): void
    {
        $select = (new SelectQuery())
            ->from('table')
            ->where('a', 1)
            ->orWhere('a', 2)
            ->wrapWhere()
            ->where('b', 3);

        $this->assertEquals(
            [
                ['AND', '('],
                ['AND', ['a', '=', new Parameter(1)]],
                ['OR', ['a', '=', new Parameter(2)]],
                ['', ')'],
                ['AND', ['b', '=', new Parameter(3)]],
            ],
            $select->getTokens()['where'],
        );
    }

    public function testWrapWhereProtectsAgainstLaterOrWhere(): void
    {
        // Simulates a scope condition guarded by wrapWhere against a later user `orWhere`.
        $select = (new SelectQuery())
            ->from('table')
            ->where('deleted_at', null)
            ->wrapWhere()
            ->orWhere('id', 5);

        $this->assertEquals(
            [
                ['AND', '('],
                ['AND', ['deleted_at', '=', new Parameter(null)]],
                ['', ')'],
                ['OR', ['id', '=', new Parameter(5)]],
            ],
            $select->getTokens()['where'],
        );
    }
}
