<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Driver\SQLite\Schema\SQLiteColumn;
use Cycle\Database\Schema\Comparator;
use Cycle\Database\Schema\State;
use PHPUnit\Framework\TestCase;

final class ComparatorTest extends TestCase
{
    /**
     * @dataProvider addedColumnsDataProvider
     */
    public function testAddedColumns(array $expected, State $current): void
    {
        $comparator = new Comparator(new State(''), $current);

        $this->assertEquals($expected, $comparator->addedColumns());
    }

    /**
     * @dataProvider alteredColumnsDataProvider
     */
    public function testAlteredColumns(array $expected, State $current): void
    {
        $initial = new State('');
        $initial->registerColumn((new SQLiteColumn('a', 'b'))->setAttributes(['readOnly' => true]));
        $initial->registerColumn(new SQLiteColumn('c', 'd'));

        $comparator = new Comparator($initial, $current);

        $this->assertEquals($expected, $comparator->alteredColumns());
    }

    /**
     * @dataProvider droppedColumnsDataProvider
     */
    public function testDroppedColumns(array $expected, State $current): void
    {
        $initial = new State('');
        $initial->registerColumn((new SQLiteColumn('a', 'b'))->setAttributes(['readOnly' => true]));
        $initial->registerColumn(new SQLiteColumn('c', 'd'));

        $comparator = new Comparator($initial, $current);

        $this->assertEquals($expected, $comparator->droppedColumns());
    }

    public static function addedColumnsDataProvider(): \Traversable
    {
        yield [[], new State('')];

        $current = new State('');
        $current->registerColumn(new SQLiteColumn('foo', 'bar'));
        yield [[new SQLiteColumn('foo', 'bar')], $current];

        $current = new State('');
        $current->registerColumn((new SQLiteColumn('foo', 'bar'))->setAttributes(['readOnly' => true]));
        yield [[], $current];

        $current = new State('');
        $current->registerColumn((new SQLiteColumn('foo', 'bar'))->setAttributes(['readOnly' => true]));
        $current->registerColumn(new SQLiteColumn('baz', 'some'));
        yield [[new SQLiteColumn('baz', 'some')], $current];
    }

    public static function alteredColumnsDataProvider(): \Traversable
    {
        $current = new State('');
        $current->registerColumn((new SQLiteColumn('a', 'b'))->setAttributes(['readOnly' => true]));
        $current->registerColumn(new SQLiteColumn('c', 'd'));
        yield [[], $current];

        $current = new State('');
        $current->registerColumn((new SQLiteColumn('changed', 'changed'))->setAttributes(['readOnly' => true]));
        $current->registerColumn(new SQLiteColumn('c', 'd'));
        yield [[], $current];

        $current = new State('');
        $current->registerColumn((new SQLiteColumn('a', 'b'))->setAttributes(['readOnly' => true]));
        $current->registerColumn((new SQLiteColumn('c', 'd'))->defaultValue('baz'));
        yield [
            [[(new SQLiteColumn('c', 'd'))->defaultValue('baz'), new SQLiteColumn('c', 'd')]],
            $current,
        ];
    }

    public static function droppedColumnsDataProvider(): \Traversable
    {
        $current = new State('');
        $current->registerColumn((new SQLiteColumn('a', 'b'))->setAttributes(['readOnly' => true]));
        $current->registerColumn(new SQLiteColumn('c', 'd'));
        yield [[], $current];

        $current = new State('');
        $current->registerColumn(new SQLiteColumn('c', 'd'));
        yield [[], $current];

        $current = new State('');
        $current->registerColumn((new SQLiteColumn('a', 'b'))->setAttributes(['readOnly' => true]));
        yield [[new SQLiteColumn('c', 'd')], $current];
    }
}
