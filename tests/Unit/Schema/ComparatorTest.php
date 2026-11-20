<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Driver\SQLite\Schema\SQLiteColumn;
use Cycle\Database\Driver\SQLite\Schema\SQLiteForeignKey;
use Cycle\Database\Driver\SQLite\Schema\SQLiteIndex;
use Cycle\Database\Schema\Comparator;
use Cycle\Database\Schema\State;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
final class ComparatorTest
{
    public static function addedColumnsDataProvider(): \Traversable
    {
        yield [[], new State('')];

        $current = new State('');
        $current->registerColumn(new SQLiteColumn('foo', 'bar'));
        yield [[new SQLiteColumn('foo', 'bar')], $current];

        $current = new State('');
        $current->registerColumn((new SQLiteColumn('foo', 'bar'))->setAttributes(['readonlySchema' => true]));
        yield [[], $current];

        $current = new State('');
        $current->registerColumn((new SQLiteColumn('foo', 'bar'))->setAttributes(['readonlySchema' => true]));
        $current->registerColumn(new SQLiteColumn('baz', 'some'));
        yield [[new SQLiteColumn('baz', 'some')], $current];
    }

    public static function alteredColumnsDataProvider(): \Traversable
    {
        $current = new State('');
        $current->registerColumn((new SQLiteColumn('a', 'b'))->setAttributes(['readonlySchema' => true]));
        $current->registerColumn(new SQLiteColumn('c', 'd'));
        yield [[], $current];

        $current = new State('');
        $current->registerColumn((new SQLiteColumn('changed', 'changed'))->setAttributes(['readonlySchema' => true]));
        $current->registerColumn(new SQLiteColumn('c', 'd'));
        yield [[], $current];

        $current = new State('');
        $current->registerColumn((new SQLiteColumn('a', 'b'))->setAttributes(['readonlySchema' => true]));
        $current->registerColumn((new SQLiteColumn('c', 'd'))->defaultValue('baz'));
        yield [
            [[(new SQLiteColumn('c', 'd'))->defaultValue('baz'), new SQLiteColumn('c', 'd')]],
            $current,
        ];
    }

    public static function droppedColumnsDataProvider(): \Traversable
    {
        $current = new State('');
        $current->registerColumn((new SQLiteColumn('a', 'b'))->setAttributes(['readonlySchema' => true]));
        $current->registerColumn(new SQLiteColumn('c', 'd'));
        yield [[], $current];

        $current = new State('');
        $current->registerColumn(new SQLiteColumn('c', 'd'));
        yield [[], $current];

        $current = new State('');
        $current->registerColumn((new SQLiteColumn('a', 'b'))->setAttributes(['readonlySchema' => true]));
        yield [[new SQLiteColumn('c', 'd')], $current];
    }

    #[DataProvider('addedColumnsDataProvider')]
    public function testAddedColumns(array $expected, State $current): void
    {
        $comparator = new Comparator(new State(''), $current);

        Assert::equals($comparator->addedColumns(), $expected);
    }

    #[DataProvider('alteredColumnsDataProvider')]
    public function testAlteredColumns(array $expected, State $current): void
    {
        $initial = new State('');
        $initial->registerColumn((new SQLiteColumn('a', 'b'))->setAttributes(['readonlySchema' => true]));
        $initial->registerColumn(new SQLiteColumn('c', 'd'));

        $comparator = new Comparator($initial, $current);

        Assert::equals($comparator->alteredColumns(), $expected);
    }

    #[DataProvider('droppedColumnsDataProvider')]
    public function testDroppedColumns(array $expected, State $current): void
    {
        $initial = new State('');
        $initial->registerColumn((new SQLiteColumn('a', 'b'))->setAttributes(['readonlySchema' => true]));
        $initial->registerColumn(new SQLiteColumn('c', 'd'));

        $comparator = new Comparator($initial, $current);

        Assert::equals($comparator->droppedColumns(), $expected);
    }

    public function testListsEveryDifference(): void
    {
        $initial = new State('t');
        $current = new State('t');
        foreach (['n1', 'n2'] as $name) {
            $current->registerColumn(self::column($name));
            $current->registerIndex(self::index($name));
            $current->registerForeignKey(self::foreignKey($name));
        }
        foreach (['k1', 'k2'] as $name) {
            $initial->registerColumn(self::column($name));
            $current->registerColumn(self::column($name)->defaultValue('changed'));
            $initial->registerIndex(self::index($name));
            $current->registerIndex(self::index($name)->unique());
            $initial->registerForeignKey(self::foreignKey($name));
            $current->registerForeignKey(self::foreignKey($name)->onDelete('CASCADE'));
        }
        foreach (['d1', 'd2'] as $name) {
            $initial->registerColumn(self::column($name));
            $initial->registerIndex(self::index($name));
            $initial->registerForeignKey(self::foreignKey($name));
        }

        $comparator = new Comparator($initial, $current);

        Assert::same(self::names($comparator->addedColumns()), ['n1', 'n2']);
        Assert::same(self::names($comparator->droppedColumns()), ['d1', 'd2']);
        Assert::same(self::pairNames($comparator->alteredColumns()), [['k1', 'k1'], ['k2', 'k2']]);
        Assert::same(self::names($comparator->addedIndexes()), ['i_n1', 'i_n2']);
        Assert::same(self::names($comparator->droppedIndexes()), ['i_d1', 'i_d2']);
        Assert::same(self::pairNames($comparator->alteredIndexes()), [['i_k1', 'i_k1'], ['i_k2', 'i_k2']]);
        Assert::same(self::columns($comparator->addedForeignKeys()), [['n1'], ['n2']]);
        Assert::same(self::columns($comparator->droppedForeignKeys()), [['d1'], ['d2']]);
        Assert::same(\array_map(self::columns(...), $comparator->alteredForeignKeys()), [[['k1'], ['k1']], [['k2'], ['k2']]]);
        Assert::same($comparator->alteredForeignKeys()[0][1]->getDeleteRule(), 'NO ACTION');
    }

    public function testHasNoChangesForEqualStates(): void
    {
        $initial = new State('t');
        $initial->registerColumn(self::column('a'));
        $current = new State('t');
        $current->registerColumn(self::column('a'));

        Assert::false((new Comparator($initial, $current))->hasChanges());
    }

    public function testChangedPrimaryKeysAreAChange(): void
    {
        $initial = new State('t');
        $initial->setPrimaryKeys(['a']);
        $current = new State('t');
        $current->setPrimaryKeys(['b']);

        Assert::true((new Comparator($initial, $current))->hasChanges());
    }

    private static function column(string $name): SQLiteColumn
    {
        return (new SQLiteColumn('t', $name))->integer();
    }

    private static function index(string $column): SQLiteIndex
    {
        return (new SQLiteIndex('t', "i_{$column}"))->columns([$column]);
    }

    private static function foreignKey(string $column): SQLiteForeignKey
    {
        return (new SQLiteForeignKey('t', '', "f_{$column}"))->columns([$column])->references('p', ['id']);
    }

    /**
     * @param list<object> $elements
     */
    private static function names(array $elements): array
    {
        return \array_map(static fn(object $element): string => $element->getName(), $elements);
    }

    /**
     * @param list<SQLiteForeignKey> $foreignKeys
     */
    private static function columns(array $foreignKeys): array
    {
        return \array_map(static fn(SQLiteForeignKey $foreignKey): array => $foreignKey->getColumns(), $foreignKeys);
    }

    /**
     * @param list<array{object, object}> $pairs
     */
    private static function pairNames(array $pairs): array
    {
        return \array_map(static fn(array $pair): array => self::names($pair), $pairs);
    }
}
