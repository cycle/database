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
use PHPUnit\Framework\TestCase;

class SQLiteOnConflictTest
{
    #[\Testo\Test]
    public function testTargetReturnsSubclassInstance(): void
    {
        $c = SQLiteOnConflict::target('email');

        \Testo\Assert::instanceOf($c, SQLiteOnConflict::class);
    }

    #[\Testo\Test]
    public function testTargetWhereWrapsString(): void
    {
        $c = SQLiteOnConflict::target('resource_key', 'route')
            ->targetWhere('resource_key IS NOT NULL');

        $tokens = $c->getIndexPredicate();
        \Testo\Assert::count($tokens, 1);
        \Testo\Assert::instanceOf($tokens[0][1], Fragment::class);
        \Testo\Assert::same((string) $tokens[0][1], 'resource_key IS NOT NULL');
    }

    #[\Testo\Test]
    public function testTargetWhereAcceptsClosureBuilder(): void
    {
        $c = SQLiteOnConflict::target('resource_key')
            ->targetWhere(static fn(OnConflictWhere $w) => $w->where('resource_key', '!=', null));

        \Testo\Assert::notSame($c->getIndexPredicate(), []);
    }

    #[\Testo\Test]
    public function testFromBase(): void
    {
        $base = OnConflict::target('email')->doUpdate(['name']);
        $sqlite = SQLiteOnConflict::from($base);

        \Testo\Assert::instanceOf($sqlite, SQLiteOnConflict::class);
        \Testo\Assert::same($sqlite->getTarget(), ['email']);
        \Testo\Assert::same($sqlite->getUpdate(), ['name']);
        \Testo\Assert::same($sqlite->getIndexPredicate(), []);
    }

    #[\Testo\Test]
    public function testFromSelfReturnsSameInstance(): void
    {
        $sqlite = SQLiteOnConflict::target('email');

        \Testo\Assert::same(SQLiteOnConflict::from($sqlite), $sqlite);
    }

    #[\Testo\Test]
    public function testFromPostgresSiblingCarriesPredicate(): void
    {
        $pg = PostgresOnConflict::target('resource_key', 'route')
            ->targetWhere('resource_key IS NOT NULL')
            ->doUpdate(['route']);

        $sqlite = SQLiteOnConflict::from($pg);

        \Testo\Assert::instanceOf($sqlite, SQLiteOnConflict::class);
        \Testo\Assert::same($sqlite->getTarget(), ['resource_key', 'route']);
        \Testo\Assert::same((string) $sqlite->getIndexPredicate()[0][1], 'resource_key IS NOT NULL');
    }

    #[\Testo\Test]
    public function testFromPostgresConstraintRejected(): void
    {
        $pg = PostgresOnConflict::onConstraint('users_email_unique')->doUpdate(['name']);

        \Testo\Expect::exception(BuilderException::class);
        SQLiteOnConflict::from($pg);
    }

    #[\Testo\Test]
    public function testFromOtherDriverSubclassRejected(): void
    {
        $mysql = MySQLOnConflict::target('email');

        \Testo\Expect::exception(BuilderException::class);
        SQLiteOnConflict::from($mysql);
    }
}
