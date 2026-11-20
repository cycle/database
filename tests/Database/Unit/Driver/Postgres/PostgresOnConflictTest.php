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
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Expect;
use Testo\Test;

#[Test]
final class PostgresOnConflictTest
{
    public function testOnConstraint(): void
    {
        $c = PostgresOnConflict::onConstraint('users_email_unique');

        Assert::same($c->getTarget(), []);
        Assert::same($c->getConstraint(), 'users_email_unique');
        Assert::same($c->getAction(), ConflictAction::Update);
    }

    #[ExpectException(BuilderException::class)]
    public function testOnConstraintEmptyRejected(): void
    {
        PostgresOnConflict::onConstraint('');
    }

    public function testTargetReturnsSubclassInstance(): void
    {
        $c = PostgresOnConflict::target('email');

        Assert::instanceOf($c, PostgresOnConflict::class);
    }

    public function testDoUpdatePreservesConstraint(): void
    {
        $c = PostgresOnConflict::onConstraint('users_email_unique')->doUpdate(['name']);

        Assert::same($c->getConstraint(), 'users_email_unique');
        Assert::same($c->getUpdate(), ['name']);
    }

    public function testFromBase(): void
    {
        $base = OnConflict::target('email')->doUpdate(['name']);
        $pg = PostgresOnConflict::from($base);

        Assert::instanceOf($pg, PostgresOnConflict::class);
        Assert::same($pg->getTarget(), ['email']);
        Assert::same($pg->getUpdate(), ['name']);
        Assert::null($pg->getConstraint());
    }

    public function testFromSelfReturnsSameInstance(): void
    {
        $pg = PostgresOnConflict::onConstraint('foo');

        Assert::same(PostgresOnConflict::from($pg), $pg);
    }

    public function testFromOtherDriverSubclassRejected(): void
    {
        $mysql = MySQLOnConflict::target('email');

        Expect::exception(BuilderException::class);
        PostgresOnConflict::from($mysql);
    }

    public function testCacheKeyIncludesConstraint(): void
    {
        $a = PostgresOnConflict::target('email')->doUpdate(['name']);
        $b = PostgresOnConflict::onConstraint('users_email_unique')->doUpdate(['name']);

        Assert::notSame($b->getCacheKey(new QueryParameters()), $a->getCacheKey(new QueryParameters()));
    }

    public function testTargetWhereWrapsString(): void
    {
        $c = PostgresOnConflict::target('resource_key', 'route')
            ->targetWhere('resource_key IS NOT NULL');

        $tokens = $c->getIndexPredicate();
        Assert::count($tokens, 1);
        Assert::same($tokens[0][0], 'AND');
        Assert::instanceOf($tokens[0][1], Fragment::class);
        Assert::same((string) $tokens[0][1], 'resource_key IS NOT NULL');
    }

    public function testTargetWhereAcceptsFragmentInterface(): void
    {
        // Expression is a FragmentInterface (not a Fragment) — it is stored as-is.
        $expr = new Expression('resource_key IS NOT NULL');
        $c = PostgresOnConflict::target('resource_key')->targetWhere($expr);

        Assert::same($c->getIndexPredicate()[0][1], $expr);
    }

    public function testTargetWhereAcceptsVariadicCondition(): void
    {
        $c = PostgresOnConflict::target('resource_key', 'route')
            ->targetWhere('priority', '>', 5);

        $tokens = $c->getIndexPredicate();
        Assert::notSame($tokens, []);
        Assert::same($tokens[0][1][0], 'priority');
        Assert::same($tokens[0][1][1], '>');
    }

    public function testTargetWhereAcceptsArrayForm(): void
    {
        $c = PostgresOnConflict::target('resource_key')
            ->targetWhere(['resource_key' => ['!=' => null]]);

        Assert::notSame($c->getIndexPredicate(), []);
    }

    public function testTargetWhereAcceptsClosureBuilder(): void
    {
        $c = PostgresOnConflict::target('resource_key', 'route')
            ->targetWhere(static fn(OnConflictWhere $w) => $w->where('resource_key', '!=', null));

        $tokens = $c->getIndexPredicate();
        Assert::notSame($tokens, []);
        Assert::same($tokens[0][1][0], 'resource_key');
        Assert::same($tokens[0][1][1], '!=');
    }

    #[ExpectException(BuilderException::class)]
    public function testTargetWhereNoArgsRejected(): void
    {
        PostgresOnConflict::target('resource_key')->targetWhere();
    }

    #[ExpectException(BuilderException::class)]
    public function testTargetWhereEmptyStringRejected(): void
    {
        PostgresOnConflict::target('resource_key')->targetWhere('');
    }

    public function testTargetWhereIsImmutable(): void
    {
        $base = PostgresOnConflict::target('resource_key');
        $withWhere = $base->targetWhere('resource_key IS NOT NULL');

        Assert::same($base->getIndexPredicate(), []);
        Assert::notSame($withWhere, $base);
    }

    public function testFromSqliteSiblingCarriesPredicate(): void
    {
        $sqlite = SQLiteOnConflict::target('resource_key', 'route')
            ->targetWhere('resource_key IS NOT NULL')
            ->doUpdate(['route']);

        $pg = PostgresOnConflict::from($sqlite);

        Assert::instanceOf($pg, PostgresOnConflict::class);
        Assert::same($pg->getTarget(), ['resource_key', 'route']);
        Assert::same($pg->getUpdate(), ['route']);
        Assert::same((string) $pg->getIndexPredicate()[0][1], 'resource_key IS NOT NULL');
        Assert::null($pg->getConstraint());
    }
}
