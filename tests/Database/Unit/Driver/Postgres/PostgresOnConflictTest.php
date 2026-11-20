<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\Postgres;

use Cycle\Database\Driver\MySQL\MySQLOnConflict;
use Cycle\Database\Driver\Postgres\PostgresOnConflict;
use Cycle\Database\Driver\SQLite\SQLiteOnConflict;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Query\ConflictAction;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Query\OnConflictWhere;
use Cycle\Database\Query\QueryParameters;
use PHPUnit\Framework\TestCase;

class PostgresOnConflictTest
{
    #[\Testo\Test]
    public function testOnConstraint(): void
    {
        $c = PostgresOnConflict::onConstraint('users_email_unique');

        \Testo\Assert::same($c->getTarget(), []);
        \Testo\Assert::same($c->getConstraint(), 'users_email_unique');
        \Testo\Assert::same($c->getAction(), ConflictAction::Update);
    }

    #[\Testo\Test]
    public function testOnConstraintEmptyRejected(): void
    {
        \Testo\Expect::exception(BuilderException::class);
        PostgresOnConflict::onConstraint('');
    }

    #[\Testo\Test]
    public function testTargetReturnsSubclassInstance(): void
    {
        $c = PostgresOnConflict::target('email');

        \Testo\Assert::instanceOf($c, PostgresOnConflict::class);
    }

    #[\Testo\Test]
    public function testDoUpdatePreservesConstraint(): void
    {
        $c = PostgresOnConflict::onConstraint('users_email_unique')->doUpdate(['name']);

        \Testo\Assert::same($c->getConstraint(), 'users_email_unique');
        \Testo\Assert::same($c->getUpdate(), ['name']);
    }

    #[\Testo\Test]
    public function testFromBase(): void
    {
        $base = OnConflict::target('email')->doUpdate(['name']);
        $pg = PostgresOnConflict::from($base);

        \Testo\Assert::instanceOf($pg, PostgresOnConflict::class);
        \Testo\Assert::same($pg->getTarget(), ['email']);
        \Testo\Assert::same($pg->getUpdate(), ['name']);
        \Testo\Assert::null($pg->getConstraint());
    }

    #[\Testo\Test]
    public function testFromSelfReturnsSameInstance(): void
    {
        $pg = PostgresOnConflict::onConstraint('foo');

        \Testo\Assert::same(PostgresOnConflict::from($pg), $pg);
    }

    #[\Testo\Test]
    public function testFromOtherDriverSubclassRejected(): void
    {
        $mysql = MySQLOnConflict::target('email');

        \Testo\Expect::exception(BuilderException::class);
        PostgresOnConflict::from($mysql);
    }

    #[\Testo\Test]
    public function testCacheKeyIncludesConstraint(): void
    {
        $a = PostgresOnConflict::target('email')->doUpdate(['name']);
        $b = PostgresOnConflict::onConstraint('users_email_unique')->doUpdate(['name']);

        \Testo\Assert::notSame($b->getCacheKey(new QueryParameters()), $a->getCacheKey(new QueryParameters()));
    }

    #[\Testo\Test]
    public function testTargetWhereWrapsString(): void
    {
        $c = PostgresOnConflict::target('resource_key', 'route')
            ->targetWhere('resource_key IS NOT NULL');

        $tokens = $c->getIndexPredicate();
        \Testo\Assert::count($tokens, 1);
        \Testo\Assert::same($tokens[0][0], 'AND');
        \Testo\Assert::instanceOf($tokens[0][1], Fragment::class);
        \Testo\Assert::same((string) $tokens[0][1], 'resource_key IS NOT NULL');
    }

    #[\Testo\Test]
    public function testTargetWhereAcceptsFragmentInterface(): void
    {
        // Expression is a FragmentInterface (not a Fragment) — it is stored as-is.
        $expr = new Expression('resource_key IS NOT NULL');
        $c = PostgresOnConflict::target('resource_key')->targetWhere($expr);

        \Testo\Assert::same($c->getIndexPredicate()[0][1], $expr);
    }

    #[\Testo\Test]
    public function testTargetWhereAcceptsVariadicCondition(): void
    {
        $c = PostgresOnConflict::target('resource_key', 'route')
            ->targetWhere('priority', '>', 5);

        $tokens = $c->getIndexPredicate();
        \Testo\Assert::notSame($tokens, []);
        \Testo\Assert::same($tokens[0][1][0], 'priority');
        \Testo\Assert::same($tokens[0][1][1], '>');
    }

    #[\Testo\Test]
    public function testTargetWhereAcceptsArrayForm(): void
    {
        $c = PostgresOnConflict::target('resource_key')
            ->targetWhere(['resource_key' => ['!=' => null]]);

        \Testo\Assert::notSame($c->getIndexPredicate(), []);
    }

    #[\Testo\Test]
    public function testTargetWhereAcceptsClosureBuilder(): void
    {
        $c = PostgresOnConflict::target('resource_key', 'route')
            ->targetWhere(static fn(OnConflictWhere $w) => $w->where('resource_key', '!=', null));

        $tokens = $c->getIndexPredicate();
        \Testo\Assert::notSame($tokens, []);
        \Testo\Assert::same($tokens[0][1][0], 'resource_key');
        \Testo\Assert::same($tokens[0][1][1], '!=');
    }

    #[\Testo\Test]
    public function testTargetWhereNoArgsRejected(): void
    {
        \Testo\Expect::exception(BuilderException::class);
        PostgresOnConflict::target('resource_key')->targetWhere();
    }

    #[\Testo\Test]
    public function testTargetWhereEmptyStringRejected(): void
    {
        \Testo\Expect::exception(BuilderException::class);
        PostgresOnConflict::target('resource_key')->targetWhere('');
    }

    #[\Testo\Test]
    public function testTargetWhereIsImmutable(): void
    {
        $base = PostgresOnConflict::target('resource_key');
        $withWhere = $base->targetWhere('resource_key IS NOT NULL');

        \Testo\Assert::same($base->getIndexPredicate(), []);
        \Testo\Assert::notSame($withWhere, $base);
    }

    #[\Testo\Test]
    public function testFromSqliteSiblingCarriesPredicate(): void
    {
        $sqlite = SQLiteOnConflict::target('resource_key', 'route')
            ->targetWhere('resource_key IS NOT NULL')
            ->doUpdate(['route']);

        $pg = PostgresOnConflict::from($sqlite);

        \Testo\Assert::instanceOf($pg, PostgresOnConflict::class);
        \Testo\Assert::same($pg->getTarget(), ['resource_key', 'route']);
        \Testo\Assert::same($pg->getUpdate(), ['route']);
        \Testo\Assert::same((string) $pg->getIndexPredicate()[0][1], 'resource_key IS NOT NULL');
        \Testo\Assert::null($pg->getConstraint());
    }
}
