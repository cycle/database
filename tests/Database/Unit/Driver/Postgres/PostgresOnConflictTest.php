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

class PostgresOnConflictTest extends TestCase
{
    public function testOnConstraint(): void
    {
        $c = PostgresOnConflict::onConstraint('users_email_unique');

        $this->assertSame([], $c->getTarget());
        $this->assertSame('users_email_unique', $c->getConstraint());
        $this->assertSame(ConflictAction::Update, $c->getAction());
    }

    public function testOnConstraintEmptyRejected(): void
    {
        $this->expectException(BuilderException::class);
        PostgresOnConflict::onConstraint('');
    }

    public function testTargetReturnsSubclassInstance(): void
    {
        $c = PostgresOnConflict::target('email');

        $this->assertInstanceOf(PostgresOnConflict::class, $c);
    }

    public function testDoUpdatePreservesConstraint(): void
    {
        $c = PostgresOnConflict::onConstraint('users_email_unique')->doUpdate(['name']);

        $this->assertSame('users_email_unique', $c->getConstraint());
        $this->assertSame(['name'], $c->getUpdate());
    }

    public function testFromBase(): void
    {
        $base = OnConflict::target('email')->doUpdate(['name']);
        $pg = PostgresOnConflict::from($base);

        $this->assertInstanceOf(PostgresOnConflict::class, $pg);
        $this->assertSame(['email'], $pg->getTarget());
        $this->assertSame(['name'], $pg->getUpdate());
        $this->assertNull($pg->getConstraint());
    }

    public function testFromSelfReturnsSameInstance(): void
    {
        $pg = PostgresOnConflict::onConstraint('foo');

        $this->assertSame($pg, PostgresOnConflict::from($pg));
    }

    public function testFromOtherDriverSubclassRejected(): void
    {
        $mysql = MySQLOnConflict::target('email');

        $this->expectException(BuilderException::class);
        PostgresOnConflict::from($mysql);
    }

    public function testCacheKeyIncludesConstraint(): void
    {
        $a = PostgresOnConflict::target('email')->doUpdate(['name']);
        $b = PostgresOnConflict::onConstraint('users_email_unique')->doUpdate(['name']);

        $this->assertNotSame(
            $a->getCacheKey(new QueryParameters()),
            $b->getCacheKey(new QueryParameters()),
        );
    }

    public function testTargetWhereWrapsString(): void
    {
        $c = PostgresOnConflict::target('resource_key', 'route')
            ->targetWhere('resource_key IS NOT NULL');

        $tokens = $c->getIndexPredicate();
        $this->assertCount(1, $tokens);
        $this->assertSame('AND', $tokens[0][0]);
        $this->assertInstanceOf(Fragment::class, $tokens[0][1]);
        $this->assertSame('resource_key IS NOT NULL', (string) $tokens[0][1]);
    }

    public function testTargetWhereAcceptsFragmentInterface(): void
    {
        // Expression is a FragmentInterface (not a Fragment) — it is stored as-is.
        $expr = new Expression('resource_key IS NOT NULL');
        $c = PostgresOnConflict::target('resource_key')->targetWhere($expr);

        $this->assertSame($expr, $c->getIndexPredicate()[0][1]);
    }

    public function testTargetWhereAcceptsVariadicCondition(): void
    {
        $c = PostgresOnConflict::target('resource_key', 'route')
            ->targetWhere('priority', '>', 5);

        $tokens = $c->getIndexPredicate();
        $this->assertNotSame([], $tokens);
        $this->assertSame('priority', $tokens[0][1][0]);
        $this->assertSame('>', $tokens[0][1][1]);
    }

    public function testTargetWhereAcceptsArrayForm(): void
    {
        $c = PostgresOnConflict::target('resource_key')
            ->targetWhere(['resource_key' => ['!=' => null]]);

        $this->assertNotSame([], $c->getIndexPredicate());
    }

    public function testTargetWhereAcceptsClosureBuilder(): void
    {
        $c = PostgresOnConflict::target('resource_key', 'route')
            ->targetWhere(static fn(OnConflictWhere $w) => $w->where('resource_key', '!=', null));

        $tokens = $c->getIndexPredicate();
        $this->assertNotSame([], $tokens);
        $this->assertSame('resource_key', $tokens[0][1][0]);
        $this->assertSame('!=', $tokens[0][1][1]);
    }

    public function testTargetWhereNoArgsRejected(): void
    {
        $this->expectException(BuilderException::class);
        PostgresOnConflict::target('resource_key')->targetWhere();
    }

    public function testTargetWhereEmptyStringRejected(): void
    {
        $this->expectException(BuilderException::class);
        PostgresOnConflict::target('resource_key')->targetWhere('');
    }

    public function testTargetWhereIsImmutable(): void
    {
        $base = PostgresOnConflict::target('resource_key');
        $withWhere = $base->targetWhere('resource_key IS NOT NULL');

        $this->assertSame([], $base->getIndexPredicate());
        $this->assertNotSame($base, $withWhere);
    }

    public function testFromSqliteSiblingCarriesPredicate(): void
    {
        $sqlite = SQLiteOnConflict::target('resource_key', 'route')
            ->targetWhere('resource_key IS NOT NULL')
            ->doUpdate(['route']);

        $pg = PostgresOnConflict::from($sqlite);

        $this->assertInstanceOf(PostgresOnConflict::class, $pg);
        $this->assertSame(['resource_key', 'route'], $pg->getTarget());
        $this->assertSame(['route'], $pg->getUpdate());
        $this->assertSame('resource_key IS NOT NULL', (string) $pg->getIndexPredicate()[0][1]);
        $this->assertNull($pg->getConstraint());
    }
}
