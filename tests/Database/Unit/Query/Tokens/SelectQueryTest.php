<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Query\Tokens;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\SelectQuery;
use PHPUnit\Framework\TestCase;

class SelectQueryTest
{
    #[\Testo\Test]
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

        \Testo\Assert::same($select->getType(), CompilerInterface::SELECT_QUERY);

        \Testo\Assert::equals($select->getTokens(), [
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
        ]);
    }

    #[\Testo\Test]
    public function testWrapWhereOnEmptyIsNoop(): void
    {
        $select = (new SelectQuery())->from('table');
        $select->wrapWhere();

        \Testo\Assert::same($select->getTokens()['where'], []);
    }

    #[\Testo\Test]
    public function testWrapWhereEnclosesExistingTokens(): void
    {
        $select = (new SelectQuery())
            ->from('table')
            ->where('a', 1)
            ->orWhere('a', 2)
            ->wrapWhere()
            ->where('b', 3);

        \Testo\Assert::equals($select->getTokens()['where'], [
            ['AND', '('],
            ['AND', ['a', '=', new Parameter(1)]],
            ['OR', ['a', '=', new Parameter(2)]],
            ['', ')'],
            ['AND', ['b', '=', new Parameter(3)]],
        ]);
    }

    #[\Testo\Test]
    public function testWrapWhereProtectsAgainstLaterOrWhere(): void
    {
        // Simulates a scope condition guarded by wrapWhere against a later user `orWhere`.
        $select = (new SelectQuery())
            ->from('table')
            ->where('deleted_at', null)
            ->wrapWhere()
            ->orWhere('id', 5);

        \Testo\Assert::equals($select->getTokens()['where'], [
            ['AND', '('],
            ['AND', ['deleted_at', '=', new Parameter(null)]],
            ['', ')'],
            ['OR', ['id', '=', new Parameter(5)]],
        ]);
    }

    #[\Testo\Test]
    public function testWrapOnWhereWithoutAnyJoinIsNoop(): void
    {
        $select = (new SelectQuery())->from('table');
        $select->wrapOnWhere();

        \Testo\Assert::same($select->getTokens()['join'], []);
    }

    #[\Testo\Test]
    public function testWrapOnWhereWithEmptyOnTokensIsNoop(): void
    {
        $select = (new SelectQuery())
            ->from('table')
            ->leftJoin('joined');
        $select->wrapOnWhere();

        \Testo\Assert::same($select->getTokens()['join'][1]['on'], []);
    }

    #[\Testo\Test]
    public function testWrapOnWhereEnclosesExistingOnTokens(): void
    {
        $select = (new SelectQuery())
            ->from('table')
            ->leftJoin('joined')
            ->onWhere('joined.a', 1)
            ->orOnWhere('joined.a', 2)
            ->wrapOnWhere()
            ->onWhere('joined.b', 3);

        \Testo\Assert::equals($select->getTokens()['join'][1]['on'], [
            ['AND', '('],
            ['AND', ['joined.a', '=', new Parameter(1)]],
            ['OR', ['joined.a', '=', new Parameter(2)]],
            ['', ')'],
            ['AND', ['joined.b', '=', new Parameter(3)]],
        ]);
    }

    #[\Testo\Test]
    public function testWrapOnWhereTargetsOnlyLastRegisteredJoin(): void
    {
        $select = (new SelectQuery())
            ->from('table')
            ->leftJoin('first')->onWhere('first.x', 1)->orOnWhere('first.x', 2)
            ->leftJoin('second')->onWhere('second.y', 10)->orOnWhere('second.y', 20)
            ->wrapOnWhere(); // affects only the second join — last registered

        $joins = $select->getTokens()['join'];

        \Testo\Assert::equals($joins[1]['on'], [
            ['AND', ['first.x', '=', new Parameter(1)]],
            ['OR', ['first.x', '=', new Parameter(2)]],
        ]);

        \Testo\Assert::equals($joins[2]['on'], [
            ['AND', '('],
            ['AND', ['second.y', '=', new Parameter(10)]],
            ['OR', ['second.y', '=', new Parameter(20)]],
            ['', ')'],
        ]);
    }

    #[\Testo\Test]
    public function testWrapOnWhereDoesNotAffectWhereTokens(): void
    {
        $select = (new SelectQuery())
            ->from('table')
            ->where('a', 1)
            ->orWhere('a', 2)
            ->leftJoin('joined')->onWhere('joined.b', 3)->orOnWhere('joined.b', 4)
            ->wrapOnWhere();

        // WHERE stays flat — only the join's ON gets wrapped.
        \Testo\Assert::equals($select->getTokens()['where'], [
            ['AND', ['a', '=', new Parameter(1)]],
            ['OR', ['a', '=', new Parameter(2)]],
        ]);
    }
}
