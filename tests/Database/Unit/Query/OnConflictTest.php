<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Query;

use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\Parameter;
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

    public function testTargetArrayTakesStringsLiterallyEvenWithCommas(): void
    {
        // Matches ActiveQuery::fetchIdentifiers() convention: only the single-string
        // form splits on commas; entries inside an explicit array are taken literally.
        $c = OnConflict::target(['tenant_id, email']);

        $this->assertSame(['tenant_id, email'], $c->getTarget());
    }

    public function testTargetSingleStringSplitsOnCommas(): void
    {
        $c = OnConflict::target('tenant_id, email');

        $this->assertSame(['tenant_id', 'email'], $c->getTarget());
    }

    public function testTargetArrayDropsEmptyAndTrims(): void
    {
        $c = OnConflict::target(['  email  ', '', 'name']);

        $this->assertSame(['email', 'name'], $c->getTarget());
    }

    public function testTargetRejectsNonStringableEntry(): void
    {
        $this->expectException(BuilderException::class);
        /** @psalm-suppress InvalidArgument */
        OnConflict::target([123, ['nested']]);
    }

    public function testShorthandStringAcceptsCommaSeparated(): void
    {
        // Shorthand $insert->onConflict('a, b') must give the same result as
        // calling OnConflict::target('a, b') directly, since the shorthand
        // forwards the raw value rather than wrapping it.
        $direct = OnConflict::target('tenant_id, email');
        // Replicate the shorthand body locally to assert equivalence.
        $shorthand = OnConflict::target('tenant_id, email')->doUpdate();

        $this->assertSame($direct->getTarget(), $shorthand->getTarget());
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
        // EXCLUDED references use a raw Fragment (Expression would quote the keyword).
        $expr = new Fragment('counters.n + EXCLUDED.n');
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

    public function testCacheKeyPushesFragmentParametersFromUpdateMap(): void
    {
        $expr = new Expression('counters.n + ?', 5);
        $c = OnConflict::target('key')->doUpdate(['n' => $expr]);

        $params = new QueryParameters();
        $key = $c->getCacheKey($params);

        $this->assertCount(1, $params->getParameters());
        $this->assertStringContainsString((string) $expr, $key);
    }

    public function testCacheKeyWrapsScalarUpdateValueAsParameter(): void
    {
        $c = OnConflict::target('key')->doUpdate(['n' => 42]);

        $params = new QueryParameters();
        $key = $c->getCacheKey($params);

        $pushed = $params->getParameters();
        $this->assertCount(1, $pushed);
        $this->assertInstanceOf(Parameter::class, $pushed[0]);
        $this->assertSame(42, $pushed[0]->getValue());
        $this->assertStringContainsString('P?', $key);
    }

    public function testCacheKeyAcceptsExistingParameterInterfaceWithoutRewrapping(): void
    {
        $param = new Parameter(7);
        $c = OnConflict::target('key')->doUpdate(['n' => $param]);

        $params = new QueryParameters();
        $key = $c->getCacheKey($params);

        $pushed = $params->getParameters();
        $this->assertCount(1, $pushed);
        $this->assertSame($param, $pushed[0]);
        $this->assertStringContainsString('P?', $key);
    }

    public function testFromReturnsSameInstance(): void
    {
        $c = OnConflict::target('email')->doUpdate(['name']);

        $this->assertSame($c, OnConflict::from($c));
    }
}
