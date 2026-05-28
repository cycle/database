<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\Postgres;

use Cycle\Database\Driver\MySQL\MySQLOnConflict;
use Cycle\Database\Driver\Postgres\PostgresOnConflict;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Query\ConflictAction;
use Cycle\Database\Query\OnConflict;
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
}
