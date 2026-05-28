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

    public function testWrapOnWhereWithoutAnyJoinIsNoop(): void
    {
        $select = (new SelectQuery())->from('table');
        $select->wrapOnWhere();

        $this->assertSame([], $select->getTokens()['join']);
    }

    public function testWrapOnWhereWithEmptyOnTokensIsNoop(): void
    {
        $select = (new SelectQuery())
            ->from('table')
            ->leftJoin('joined');
        $select->wrapOnWhere();

        $this->assertSame([], $select->getTokens()['join'][1]['on']);
    }

    public function testWrapOnWhereEnclosesExistingOnTokens(): void
    {
        $select = (new SelectQuery())
            ->from('table')
            ->leftJoin('joined')
            ->onWhere('joined.a', 1)
            ->orOnWhere('joined.a', 2)
            ->wrapOnWhere()
            ->onWhere('joined.b', 3);

        $this->assertEquals(
            [
                ['AND', '('],
                ['AND', ['joined.a', '=', new Parameter(1)]],
                ['OR', ['joined.a', '=', new Parameter(2)]],
                ['', ')'],
                ['AND', ['joined.b', '=', new Parameter(3)]],
            ],
            $select->getTokens()['join'][1]['on'],
        );
    }

    public function testWrapOnWhereTargetsOnlyLastRegisteredJoin(): void
    {
        $select = (new SelectQuery())
            ->from('table')
            ->leftJoin('first')->onWhere('first.x', 1)->orOnWhere('first.x', 2)
            ->leftJoin('second')->onWhere('second.y', 10)->orOnWhere('second.y', 20)
            ->wrapOnWhere(); // affects only the second join — last registered

        $joins = $select->getTokens()['join'];

        $this->assertEquals(
            [
                ['AND', ['first.x', '=', new Parameter(1)]],
                ['OR', ['first.x', '=', new Parameter(2)]],
            ],
            $joins[1]['on'],
        );

        $this->assertEquals(
            [
                ['AND', '('],
                ['AND', ['second.y', '=', new Parameter(10)]],
                ['OR', ['second.y', '=', new Parameter(20)]],
                ['', ')'],
            ],
            $joins[2]['on'],
        );
    }

    public function testWrapOnWhereDoesNotAffectWhereTokens(): void
    {
        $select = (new SelectQuery())
            ->from('table')
            ->where('a', 1)
            ->orWhere('a', 2)
            ->leftJoin('joined')->onWhere('joined.b', 3)->orOnWhere('joined.b', 4)
            ->wrapOnWhere();

        // WHERE stays flat — only the join's ON gets wrapped.
        $this->assertEquals(
            [
                ['AND', ['a', '=', new Parameter(1)]],
                ['OR', ['a', '=', new Parameter(2)]],
            ],
            $select->getTokens()['where'],
        );
    }
}
