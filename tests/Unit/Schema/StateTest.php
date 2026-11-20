<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Database\Schema\State;
use Testo\Assert;
use Testo\Test;

#[Test]
final class StateTest
{
    public function testGetPrimaryKeys(): void
    {
        $state = new State('foo');

        $state->registerColumn((new PostgresColumn('foo', 'smallPrimary'))->smallPrimary());
        $state->registerColumn((new PostgresColumn('foo', 'primary'))->primary());
        $state->registerColumn((new PostgresColumn('foo', 'bigPrimary'))->bigPrimary());
        $state->registerColumn((new PostgresColumn('foo', 'string'))->string());
        $state->registerColumn((new PostgresColumn('foo', 'integer'))->integer());

        $state->setPrimaryKeys(['foo', 'bar']);

        Assert::same($state->getPrimaryKeys(), ['foo', 'bar', 'smallPrimary', 'primary', 'bigPrimary']);
    }
}
