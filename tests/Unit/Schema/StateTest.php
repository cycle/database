<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Database\Driver\SQLite\Schema\SQLiteColumn;
use Cycle\Database\Driver\SQLite\Schema\SQLiteForeignKey;
use Cycle\Database\Driver\SQLite\Schema\SQLiteIndex;
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

    public function testListsARepeatedPrimaryKeyOnce(): void
    {
        $state = new State('foo');

        $state->setPrimaryKeys(['id', 'id']);

        Assert::same($state->getPrimaryKeys(), ['id']);
    }

    public function testForgetsTheGivenColumn(): void
    {
        $state = new State('t');
        $column = (new SQLiteColumn('t', 'a'))->integer();
        $state->registerColumn($column);

        $state->forgetColumn($column);

        Assert::false($state->hasColumn('a'));
    }

    public function testForgetsOnlyTheGivenIndex(): void
    {
        $state = new State('t');
        $state->registerIndex((new SQLiteIndex('t', 'i_a'))->columns(['a']));
        $index = (new SQLiteIndex('t', 'i_b'))->columns(['b']);
        $state->registerIndex($index);

        $state->forgetIndex($index);

        Assert::true($state->hasIndex(['a']));
        Assert::false($state->hasIndex(['b']));
    }

    public function testForgetsOnlyTheGivenForeignKey(): void
    {
        $state = new State('t');
        $state->registerForeignKey((new SQLiteForeignKey('t', '', 'f_a'))->columns(['a'])->references('p', ['id']));
        $foreignKey = (new SQLiteForeignKey('t', '', 'f_b'))->columns(['b'])->references('p', ['id']);
        $state->registerForeignKey($foreignKey);

        $state->forgetForeignKey($foreignKey);

        Assert::true($state->hasForeignKey(['a']));
        Assert::false($state->hasForeignKey(['b']));
    }

    public function testCloneCopiesEveryElement(): void
    {
        $state = new State('t');
        $state->registerColumn((new SQLiteColumn('t', 'a'))->integer()->nullable(false));
        $state->registerIndex((new SQLiteIndex('t', 'i_a'))->columns(['a']));
        $state->registerForeignKey((new SQLiteForeignKey('t', '', 'f_a'))->columns(['a'])->references('p', ['id']));

        $copy = clone $state;
        $copy->findColumn('a')->nullable(true);
        $copy->findIndex(['a'])->unique();
        $copy->findForeignKey(['a'])->onDelete('CASCADE');

        Assert::false($state->findColumn('a')->isNullable());
        Assert::false($state->findIndex(['a'])->isUnique());
        Assert::same($state->findForeignKey(['a'])->getDeleteRule(), 'NO ACTION');
    }

    public function testSyncCopiesTheElementsUnderTheirCurrentNames(): void
    {
        $source = new State('t');
        $column = (new SQLiteColumn('t', 'a'))->integer();
        $source->registerColumn($column);
        $column->setName('b');
        $source->registerForeignKey((new SQLiteForeignKey('t', '', 'f_a'))->columns(['b'])->references('p', ['id']));

        $target = (new State('t'))->syncState($source);
        $source->findForeignKey(['b'])->onDelete('CASCADE');

        Assert::same(\array_keys($target->getColumns()), ['b']);
        Assert::same($target->findForeignKey(['b'])->getDeleteRule(), 'NO ACTION');
    }
}
