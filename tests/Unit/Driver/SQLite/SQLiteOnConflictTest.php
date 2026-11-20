<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\SQLite;

use Cycle\Database\Driver\MySQL\MySQLOnConflict;
use Cycle\Database\Driver\Postgres\PostgresOnConflict;
use Cycle\Database\Driver\SQLite\SQLiteOnConflict;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Query\OnConflictWhere;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class SQLiteOnConflictTest
{
    public function testTargetReturnsSubclassInstance(): void
    {
        $c = SQLiteOnConflict::target('email');

        Assert::instanceOf($c, SQLiteOnConflict::class);
    }

    public function testTargetWhereWrapsString(): void
    {
        $c = SQLiteOnConflict::target('resource_key', 'route')
            ->targetWhere('resource_key IS NOT NULL');

        $tokens = $c->getIndexPredicate();
        Assert::count($tokens, 1);
        Assert::instanceOf($tokens[0][1], Fragment::class);
        Assert::same((string) $tokens[0][1], 'resource_key IS NOT NULL');
    }

    public function testTargetWhereAcceptsClosureBuilder(): void
    {
        $c = SQLiteOnConflict::target('resource_key')
            ->targetWhere(static fn(OnConflictWhere $w) => $w->where('resource_key', '!=', null));

        Assert::notSame($c->getIndexPredicate(), []);
    }

    public function testFromBase(): void
    {
        $base = OnConflict::target('email')->doUpdate(['name']);
        $sqlite = SQLiteOnConflict::from($base);

        Assert::instanceOf($sqlite, SQLiteOnConflict::class);
        Assert::same($sqlite->getTarget(), ['email']);
        Assert::same($sqlite->getUpdate(), ['name']);
        Assert::same($sqlite->getIndexPredicate(), []);
    }

    public function testFromSelfReturnsSameInstance(): void
    {
        $sqlite = SQLiteOnConflict::target('email');

        Assert::same(SQLiteOnConflict::from($sqlite), $sqlite);
    }

    public function testFromPostgresSiblingCarriesPredicate(): void
    {
        $pg = PostgresOnConflict::target('resource_key', 'route')
            ->targetWhere('resource_key IS NOT NULL')
            ->doUpdate(['route']);

        $sqlite = SQLiteOnConflict::from($pg);

        Assert::instanceOf($sqlite, SQLiteOnConflict::class);
        Assert::same($sqlite->getTarget(), ['resource_key', 'route']);
        Assert::same((string) $sqlite->getIndexPredicate()[0][1], 'resource_key IS NOT NULL');
    }

    public function testFromPostgresConstraintRejected(): void
    {
        $pg = PostgresOnConflict::onConstraint('users_email_unique')->doUpdate(['name']);

        Expect::exception(BuilderException::class);
        SQLiteOnConflict::from($pg);
    }

    public function testFromOtherDriverSubclassRejected(): void
    {
        $mysql = MySQLOnConflict::target('email');

        Expect::exception(BuilderException::class);
        SQLiteOnConflict::from($mysql);
    }
}
