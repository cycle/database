<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Query;

use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Query\ConflictAction;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Query\QueryParameters;
use PHPUnit\Framework\TestCase;

class OnConflictTest extends TestCase
{
    public function testTargetSingleColumn(): void
    {
        $c = OnConflict::target('email');

        $this->assertSame(['email'], $c->getTarget());
        $this->assertSame(ConflictAction::Update, $c->getAction());
        $this->assertNull($c->getUpdate());
    }

    public function testTargetMultipleColumnsVariadic(): void
    {
        $c = OnConflict::target('tenant_id', 'email');

        $this->assertSame(['tenant_id', 'email'], $c->getTarget());
    }

    public function testTargetCommaSeparatedString(): void
    {
        $c = OnConflict::target('tenant_id, email');

        $this->assertSame(['tenant_id', 'email'], $c->getTarget());
    }

    public function testTargetArray(): void
    {
        $c = OnConflict::target(['tenant_id', 'email']);

        $this->assertSame(['tenant_id', 'email'], $c->getTarget());
    }

    public function testTargetEmptyRejected(): void
    {
        $this->expectException(BuilderException::class);
        OnConflict::target();
    }

    public function testDoNothing(): void
    {
        $c = OnConflict::target('email')->doNothing();

        $this->assertSame(ConflictAction::Nothing, $c->getAction());
        $this->assertNull($c->getUpdate());
    }

    public function testDoUpdateAllColumns(): void
    {
        $c = OnConflict::target('email')->doUpdate();

        $this->assertSame(ConflictAction::Update, $c->getAction());
        $this->assertNull($c->getUpdate());
    }

    public function testDoUpdateColumnList(): void
    {
        $c = OnConflict::target('email')->doUpdate(['name', 'updated_at']);

        $this->assertSame(['name', 'updated_at'], $c->getUpdate());
    }

    public function testDoUpdateColumnMap(): void
    {
        $expr = new Expression('counters.n + EXCLUDED.n');
        $c = OnConflict::target('key')->doUpdate(['n' => $expr]);

        $this->assertSame(['n' => $expr], $c->getUpdate());
    }

    public function testImmutability(): void
    {
        $base = OnConflict::target('email');
        $withUpdate = $base->doUpdate(['name']);
        $withNothing = $base->doNothing();

        $this->assertNull($base->getUpdate());
        $this->assertSame(ConflictAction::Update, $base->getAction());

        $this->assertSame(['name'], $withUpdate->getUpdate());
        $this->assertSame(ConflictAction::Update, $withUpdate->getAction());

        $this->assertNull($withNothing->getUpdate());
        $this->assertSame(ConflictAction::Nothing, $withNothing->getAction());
    }

    public function testCacheKeyStableForSameShape(): void
    {
        $a = OnConflict::target('email')->doUpdate(['name']);
        $b = OnConflict::target('email')->doUpdate(['name']);

        $this->assertSame(
            $a->getCacheKey(new QueryParameters()),
            $b->getCacheKey(new QueryParameters()),
        );
    }

    public function testCacheKeyDiffersForDifferentAction(): void
    {
        $update = OnConflict::target('email')->doUpdate();
        $nothing = OnConflict::target('email')->doNothing();

        $this->assertNotSame(
            $update->getCacheKey(new QueryParameters()),
            $nothing->getCacheKey(new QueryParameters()),
        );
    }

    public function testCacheKeyDiffersForDifferentUpdateColumns(): void
    {
        $a = OnConflict::target('email')->doUpdate(['name']);
        $b = OnConflict::target('email')->doUpdate(['name', 'visits']);

        $this->assertNotSame(
            $a->getCacheKey(new QueryParameters()),
            $b->getCacheKey(new QueryParameters()),
        );
    }
}
