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

class SQLiteOnConflictTest extends TestCase
{
    public function testTargetReturnsSubclassInstance(): void
    {
        $c = SQLiteOnConflict::target('email');

        $this->assertInstanceOf(SQLiteOnConflict::class, $c);
    }

    public function testTargetWhereWrapsString(): void
    {
        $c = SQLiteOnConflict::target('resource_key', 'route')
            ->targetWhere('resource_key IS NOT NULL');

        $tokens = $c->getIndexPredicate();
        $this->assertCount(1, $tokens);
        $this->assertInstanceOf(Fragment::class, $tokens[0][1]);
        $this->assertSame('resource_key IS NOT NULL', (string) $tokens[0][1]);
    }

    public function testTargetWhereAcceptsClosureBuilder(): void
    {
        $c = SQLiteOnConflict::target('resource_key')
            ->targetWhere(static fn(OnConflictWhere $w) => $w->where('resource_key', '!=', null));

        $this->assertNotSame([], $c->getIndexPredicate());
    }

    public function testFromBase(): void
    {
        $base = OnConflict::target('email')->doUpdate(['name']);
        $sqlite = SQLiteOnConflict::from($base);

        $this->assertInstanceOf(SQLiteOnConflict::class, $sqlite);
        $this->assertSame(['email'], $sqlite->getTarget());
        $this->assertSame(['name'], $sqlite->getUpdate());
        $this->assertSame([], $sqlite->getIndexPredicate());
    }

    public function testFromSelfReturnsSameInstance(): void
    {
        $sqlite = SQLiteOnConflict::target('email');

        $this->assertSame($sqlite, SQLiteOnConflict::from($sqlite));
    }

    public function testFromPostgresSiblingCarriesPredicate(): void
    {
        $pg = PostgresOnConflict::target('resource_key', 'route')
            ->targetWhere('resource_key IS NOT NULL')
            ->doUpdate(['route']);

        $sqlite = SQLiteOnConflict::from($pg);

        $this->assertInstanceOf(SQLiteOnConflict::class, $sqlite);
        $this->assertSame(['resource_key', 'route'], $sqlite->getTarget());
        $this->assertSame('resource_key IS NOT NULL', (string) $sqlite->getIndexPredicate()[0][1]);
    }

    public function testFromPostgresConstraintRejected(): void
    {
        $pg = PostgresOnConflict::onConstraint('users_email_unique')->doUpdate(['name']);

        $this->expectException(BuilderException::class);
        SQLiteOnConflict::from($pg);
    }

    public function testFromOtherDriverSubclassRejected(): void
    {
        $mysql = MySQLOnConflict::target('email');

        $this->expectException(BuilderException::class);
        SQLiteOnConflict::from($mysql);
    }
}
