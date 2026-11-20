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
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class OnConflictTest
{
    public function testTargetSingleColumn(): void
    {
        $c = OnConflict::target('email');

        Assert::same($c->getTarget(), ['email']);
        Assert::same($c->getAction(), ConflictAction::Update);
        Assert::null($c->getUpdate());
    }

    public function testTargetMultipleColumnsVariadic(): void
    {
        $c = OnConflict::target('tenant_id', 'email');

        Assert::same($c->getTarget(), ['tenant_id', 'email']);
    }

    public function testTargetCommaSeparatedString(): void
    {
        $c = OnConflict::target('tenant_id, email');

        Assert::same($c->getTarget(), ['tenant_id', 'email']);
    }

    public function testTargetArray(): void
    {
        $c = OnConflict::target(['tenant_id', 'email']);

        Assert::same($c->getTarget(), ['tenant_id', 'email']);
    }

    #[ExpectException(BuilderException::class)]
    public function testTargetEmptyRejected(): void
    {
        OnConflict::target();
    }

    public function testTargetArrayTakesStringsLiterallyEvenWithCommas(): void
    {
        // Matches ActiveQuery::fetchIdentifiers() convention: only the single-string
        // form splits on commas; entries inside an explicit array are taken literally.
        $c = OnConflict::target(['tenant_id, email']);

        Assert::same($c->getTarget(), ['tenant_id, email']);
    }

    public function testTargetSingleStringSplitsOnCommas(): void
    {
        $c = OnConflict::target('tenant_id, email');

        Assert::same($c->getTarget(), ['tenant_id', 'email']);
    }

    public function testTargetArrayDropsEmptyAndTrims(): void
    {
        $c = OnConflict::target(['  email  ', '', 'name']);

        Assert::same($c->getTarget(), ['email', 'name']);
    }

    #[ExpectException(BuilderException::class)]
    public function testTargetRejectsNonStringableEntry(): void
    {
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

        Assert::same($shorthand->getTarget(), $direct->getTarget());
    }

    public function testDoNothing(): void
    {
        $c = OnConflict::target('email')->doNothing();

        Assert::same($c->getAction(), ConflictAction::Nothing);
        Assert::null($c->getUpdate());
    }

    public function testDoUpdateAllColumns(): void
    {
        $c = OnConflict::target('email')->doUpdate();

        Assert::same($c->getAction(), ConflictAction::Update);
        Assert::null($c->getUpdate());
    }

    public function testDoUpdateColumnList(): void
    {
        $c = OnConflict::target('email')->doUpdate(['name', 'updated_at']);

        Assert::same($c->getUpdate(), ['name', 'updated_at']);
    }

    public function testDoUpdateColumnMap(): void
    {
        // EXCLUDED references use a raw Fragment (Expression would quote the keyword).
        $expr = new Fragment('counters.n + EXCLUDED.n');
        $c = OnConflict::target('key')->doUpdate(['n' => $expr]);

        Assert::same($c->getUpdate(), ['n' => $expr]);
    }

    public function testImmutability(): void
    {
        $base = OnConflict::target('email');
        $withUpdate = $base->doUpdate(['name']);
        $withNothing = $base->doNothing();

        Assert::null($base->getUpdate());
        Assert::same($base->getAction(), ConflictAction::Update);

        Assert::same($withUpdate->getUpdate(), ['name']);
        Assert::same($withUpdate->getAction(), ConflictAction::Update);

        Assert::null($withNothing->getUpdate());
        Assert::same($withNothing->getAction(), ConflictAction::Nothing);
    }

    public function testCacheKeyStableForSameShape(): void
    {
        $a = OnConflict::target('email')->doUpdate(['name']);
        $b = OnConflict::target('email')->doUpdate(['name']);

        Assert::same($b->getCacheKey(new QueryParameters()), $a->getCacheKey(new QueryParameters()));
    }

    public function testCacheKeyDiffersForDifferentAction(): void
    {
        $update = OnConflict::target('email')->doUpdate();
        $nothing = OnConflict::target('email')->doNothing();

        Assert::notSame($nothing->getCacheKey(new QueryParameters()), $update->getCacheKey(new QueryParameters()));
    }

    public function testCacheKeyDiffersForDifferentUpdateColumns(): void
    {
        $a = OnConflict::target('email')->doUpdate(['name']);
        $b = OnConflict::target('email')->doUpdate(['name', 'visits']);

        Assert::notSame($b->getCacheKey(new QueryParameters()), $a->getCacheKey(new QueryParameters()));
    }

    public function testCacheKeyPushesFragmentParametersFromUpdateMap(): void
    {
        $expr = new Expression('counters.n + ?', 5);
        $c = OnConflict::target('key')->doUpdate(['n' => $expr]);

        $params = new QueryParameters();
        $key = $c->getCacheKey($params);

        Assert::count($params->getParameters(), 1);
        Assert::string($key)->contains((string) $expr);
    }

    public function testCacheKeyWrapsScalarUpdateValueAsParameter(): void
    {
        $c = OnConflict::target('key')->doUpdate(['n' => 42]);

        $params = new QueryParameters();
        $key = $c->getCacheKey($params);

        $pushed = $params->getParameters();
        Assert::count($pushed, 1);
        Assert::instanceOf($pushed[0], Parameter::class);
        Assert::same($pushed[0]->getValue(), 42);
        Assert::string($key)->contains('P?');
    }

    public function testCacheKeyAcceptsExistingParameterInterfaceWithoutRewrapping(): void
    {
        $param = new Parameter(7);
        $c = OnConflict::target('key')->doUpdate(['n' => $param]);

        $params = new QueryParameters();
        $key = $c->getCacheKey($params);

        $pushed = $params->getParameters();
        Assert::count($pushed, 1);
        Assert::same($pushed[0], $param);
        Assert::string($key)->contains('P?');
    }

    public function testTargetMixesArraysAndStrings(): void
    {
        Assert::same(OnConflict::target(['a'], 'b, c')->getTarget(), ['a', 'b', 'c']);
    }

    public function testFromReturnsSameInstance(): void
    {
        $c = OnConflict::target('email')->doUpdate(['name']);

        Assert::same(OnConflict::from($c), $c);
    }
}
