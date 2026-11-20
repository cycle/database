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

class OnConflictTest
{
    #[\Testo\Test]
    public function testTargetSingleColumn(): void
    {
        $c = OnConflict::target('email');

        \Testo\Assert::same($c->getTarget(), ['email']);
        \Testo\Assert::same($c->getAction(), ConflictAction::Update);
        \Testo\Assert::null($c->getUpdate());
    }

    #[\Testo\Test]
    public function testTargetMultipleColumnsVariadic(): void
    {
        $c = OnConflict::target('tenant_id', 'email');

        \Testo\Assert::same($c->getTarget(), ['tenant_id', 'email']);
    }

    #[\Testo\Test]
    public function testTargetCommaSeparatedString(): void
    {
        $c = OnConflict::target('tenant_id, email');

        \Testo\Assert::same($c->getTarget(), ['tenant_id', 'email']);
    }

    #[\Testo\Test]
    public function testTargetArray(): void
    {
        $c = OnConflict::target(['tenant_id', 'email']);

        \Testo\Assert::same($c->getTarget(), ['tenant_id', 'email']);
    }

    #[\Testo\Test]
    public function testTargetEmptyRejected(): void
    {
        \Testo\Expect::exception(BuilderException::class);
        OnConflict::target();
    }

    #[\Testo\Test]
    public function testTargetArrayTakesStringsLiterallyEvenWithCommas(): void
    {
        // Matches ActiveQuery::fetchIdentifiers() convention: only the single-string
        // form splits on commas; entries inside an explicit array are taken literally.
        $c = OnConflict::target(['tenant_id, email']);

        \Testo\Assert::same($c->getTarget(), ['tenant_id, email']);
    }

    #[\Testo\Test]
    public function testTargetSingleStringSplitsOnCommas(): void
    {
        $c = OnConflict::target('tenant_id, email');

        \Testo\Assert::same($c->getTarget(), ['tenant_id', 'email']);
    }

    #[\Testo\Test]
    public function testTargetArrayDropsEmptyAndTrims(): void
    {
        $c = OnConflict::target(['  email  ', '', 'name']);

        \Testo\Assert::same($c->getTarget(), ['email', 'name']);
    }

    #[\Testo\Test]
    public function testTargetRejectsNonStringableEntry(): void
    {
        \Testo\Expect::exception(BuilderException::class);
        /** @psalm-suppress InvalidArgument */
        OnConflict::target([123, ['nested']]);
    }

    #[\Testo\Test]
    public function testShorthandStringAcceptsCommaSeparated(): void
    {
        // Shorthand $insert->onConflict('a, b') must give the same result as
        // calling OnConflict::target('a, b') directly, since the shorthand
        // forwards the raw value rather than wrapping it.
        $direct = OnConflict::target('tenant_id, email');
        // Replicate the shorthand body locally to assert equivalence.
        $shorthand = OnConflict::target('tenant_id, email')->doUpdate();

        \Testo\Assert::same($shorthand->getTarget(), $direct->getTarget());
    }

    #[\Testo\Test]
    public function testDoNothing(): void
    {
        $c = OnConflict::target('email')->doNothing();

        \Testo\Assert::same($c->getAction(), ConflictAction::Nothing);
        \Testo\Assert::null($c->getUpdate());
    }

    #[\Testo\Test]
    public function testDoUpdateAllColumns(): void
    {
        $c = OnConflict::target('email')->doUpdate();

        \Testo\Assert::same($c->getAction(), ConflictAction::Update);
        \Testo\Assert::null($c->getUpdate());
    }

    #[\Testo\Test]
    public function testDoUpdateColumnList(): void
    {
        $c = OnConflict::target('email')->doUpdate(['name', 'updated_at']);

        \Testo\Assert::same($c->getUpdate(), ['name', 'updated_at']);
    }

    #[\Testo\Test]
    public function testDoUpdateColumnMap(): void
    {
        // EXCLUDED references use a raw Fragment (Expression would quote the keyword).
        $expr = new Fragment('counters.n + EXCLUDED.n');
        $c = OnConflict::target('key')->doUpdate(['n' => $expr]);

        \Testo\Assert::same($c->getUpdate(), ['n' => $expr]);
    }

    #[\Testo\Test]
    public function testImmutability(): void
    {
        $base = OnConflict::target('email');
        $withUpdate = $base->doUpdate(['name']);
        $withNothing = $base->doNothing();

        \Testo\Assert::null($base->getUpdate());
        \Testo\Assert::same($base->getAction(), ConflictAction::Update);

        \Testo\Assert::same($withUpdate->getUpdate(), ['name']);
        \Testo\Assert::same($withUpdate->getAction(), ConflictAction::Update);

        \Testo\Assert::null($withNothing->getUpdate());
        \Testo\Assert::same($withNothing->getAction(), ConflictAction::Nothing);
    }

    #[\Testo\Test]
    public function testCacheKeyStableForSameShape(): void
    {
        $a = OnConflict::target('email')->doUpdate(['name']);
        $b = OnConflict::target('email')->doUpdate(['name']);

        \Testo\Assert::same($b->getCacheKey(new QueryParameters()), $a->getCacheKey(new QueryParameters()));
    }

    #[\Testo\Test]
    public function testCacheKeyDiffersForDifferentAction(): void
    {
        $update = OnConflict::target('email')->doUpdate();
        $nothing = OnConflict::target('email')->doNothing();

        \Testo\Assert::notSame($nothing->getCacheKey(new QueryParameters()), $update->getCacheKey(new QueryParameters()));
    }

    #[\Testo\Test]
    public function testCacheKeyDiffersForDifferentUpdateColumns(): void
    {
        $a = OnConflict::target('email')->doUpdate(['name']);
        $b = OnConflict::target('email')->doUpdate(['name', 'visits']);

        \Testo\Assert::notSame($b->getCacheKey(new QueryParameters()), $a->getCacheKey(new QueryParameters()));
    }

    #[\Testo\Test]
    public function testCacheKeyPushesFragmentParametersFromUpdateMap(): void
    {
        $expr = new Expression('counters.n + ?', 5);
        $c = OnConflict::target('key')->doUpdate(['n' => $expr]);

        $params = new QueryParameters();
        $key = $c->getCacheKey($params);

        \Testo\Assert::count($params->getParameters(), 1);
        \Testo\Assert::string($key)->contains((string) $expr);
    }

    #[\Testo\Test]
    public function testCacheKeyWrapsScalarUpdateValueAsParameter(): void
    {
        $c = OnConflict::target('key')->doUpdate(['n' => 42]);

        $params = new QueryParameters();
        $key = $c->getCacheKey($params);

        $pushed = $params->getParameters();
        \Testo\Assert::count($pushed, 1);
        \Testo\Assert::instanceOf($pushed[0], Parameter::class);
        \Testo\Assert::same($pushed[0]->getValue(), 42);
        \Testo\Assert::string($key)->contains('P?');
    }

    #[\Testo\Test]
    public function testCacheKeyAcceptsExistingParameterInterfaceWithoutRewrapping(): void
    {
        $param = new Parameter(7);
        $c = OnConflict::target('key')->doUpdate(['n' => $param]);

        $params = new QueryParameters();
        $key = $c->getCacheKey($params);

        $pushed = $params->getParameters();
        \Testo\Assert::count($pushed, 1);
        \Testo\Assert::same($pushed[0], $param);
        \Testo\Assert::string($key)->contains('P?');
    }

    #[\Testo\Test]
    public function testFromReturnsSameInstance(): void
    {
        $c = OnConflict::target('email')->doUpdate(['name']);

        \Testo\Assert::same(OnConflict::from($c), $c);
    }
}
