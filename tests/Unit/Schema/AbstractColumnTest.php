<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Driver\MySQL\Schema\MySQLColumn;
use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Database\Driver\SQLite\Schema\SQLiteColumn;
use Cycle\Database\Driver\SQLServer\Schema\SQLServerColumn;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractColumn;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class AbstractColumnTest
{
    private AbstractColumn $column;

    public function testReadonlySchemaFalseByDefault(): void
    {
        Assert::false($this->column->isReadonlySchema());
    }

    public function testReadonlySchema(): void
    {
        Assert::false($this->column->isReadonlySchema());

        $this->column->setAttributes(['readonlySchema' => true]);

        Assert::true($this->column->isReadonlySchema());
    }

    public function readsDatetimeDefaultsInItsTimezone(): void
    {
        $column = new SQLiteColumn('t', 'c', new \DateTimeZone('Asia/Tokyo'));
        $column->datetime();
        $column->defaultValue('2000-01-02 03:04:05');

        Assert::same($column->getDefaultValue()->getTimezone()->getName(), 'Asia/Tokyo');
    }

    public function readsADateDefaultAsADateString(): void
    {
        $column = new SQLiteColumn('t', 'c');
        $column->date();
        $column->defaultValue('2000-01-02 03:04:05');

        Assert::same($column->getDefaultValue(), '2000-01-02');
    }

    public function returnsACopyOfAMutableDatetimeDefault(): void
    {
        $column = new SQLiteColumn('t', 'c');
        $column->datetime();
        $column->defaultValue(new \DateTime('2000-01-02 03:04:05'));

        $column->getDefaultValue()->modify('+1 day');

        Assert::same($column->getDefaultValue()->format('Y-m-d'), '2000-01-02');
    }

    public function mapsAnUppercaseTypeToItsAbstractType(): void
    {
        $column = new SQLiteColumn('t', 'c');

        $column->type('INTEGER');

        Assert::same($column->getAbstractType(), 'integer');
    }

    public function skipsMismatchingCandidatesOfAnAbstractType(): void
    {
        $column = new class('t', 'c') extends SQLiteColumn {
            protected array $reverseMapping = [
                'custom' => [['type' => 'other'], ['type' => 'foo', 'size' => 5], 'Foo'],
                'fallback' => ['foo'],
            ];
        };

        $column->type('FOO');

        Assert::same($column->getAbstractType(), 'custom');
    }

    public function matchesAnArrayCandidateRegardlessOfCase(): void
    {
        $column = new class('t', 'c') extends SQLiteColumn {
            protected array $reverseMapping = [
                'custom' => [['type' => 'Bar']],
            ];
        };

        $column->type('BAR');

        Assert::same($column->getAbstractType(), 'custom');
    }

    public function translatesTheNowKeywordForTheDriver(): void
    {
        $column = new SQLServerColumn('t', 'c');
        $column->string();

        $column->defaultValue(AbstractColumn::DATETIME_NOW);

        Assert::same($column->getDefaultValue(), 'getdate()');
    }

    public function keepsAnOrdinaryDefault(): void
    {
        $column = new SQLServerColumn('t', 'c');
        $column->string();

        $column->defaultValue('x');

        Assert::same($column->getDefaultValue(), 'x');
    }

    public function encodesAnArrayDefaultOnlyForJsonColumns(): void
    {
        $json = new MySQLColumn('t', 'c');
        $json->json();
        $json->defaultValue(['a' => 1]);
        $string = new MySQLColumn('t', 'c');
        $string->string();
        $string->defaultValue(['a' => 1]);

        Assert::same($json->getDefaultValue(), '{"a":1}');
        Assert::notSame($string->getDefaultValue(), '{"a":1}');
    }

    public function storesEnumValuesAsStrings(): void
    {
        $column = new MySQLColumn('t', 'c');

        $column->enum([1, 2]);

        Assert::same($column->getEnumValues(), ['1', '2']);
    }

    public function acceptsAnEmptyString(): void
    {
        $column = new SQLiteColumn('t', 'c');

        $column->string(0);

        Assert::same($column->getSize(), 0);
    }

    public function rejectsANegativeStringLength(): never
    {
        Expect::exception(SchemaException::class)->withMessage('Invalid string length value');

        (new SQLiteColumn('t', 'c'))->string(-1);
    }

    public function fillsTheAttributesOfADatetime(): void
    {
        $column = new PostgresColumn('t', 'c');

        $column->datetime(0, withTimezone: true);

        Assert::true($column->getAttributes()['withTimezone']);
    }

    public function comparesDefaultsStrictlyOnlyWhenTheyDifferLoosely(): void
    {
        $column = new SQLiteColumn('t', 'c');
        $column->integer();
        $column->defaultValue(1);
        $initial = new SQLiteColumn('t', 'c');
        $initial->integer();
        $initial->defaultValue('1');

        Assert::true($column->compare($initial));
    }

    public function comparesTheRestOfAColumnWithAUserType(): void
    {
        $column = new SQLiteColumn('t', 'c');
        $column->type('geometry');
        $column->nullable(false);
        $initial = new SQLiteColumn('t', 'c');
        $initial->type('geometry');
        $initial->nullable(true);

        Assert::false($column->compare($initial));
    }

    public function setsAnAttributeThroughItsNamedMethod(): void
    {
        $column = new MySQLColumn('t', 'c');
        $column->integer();

        $column->comment('note');

        Assert::same($column->getAttributes()['comment'], 'note');
    }

    public function keepsANamedArgumentOfAnAttributeMethodAsAnExtraAttribute(): void
    {
        $column = new MySQLColumn('t', 'c');
        $column->integer();

        $column->comment(note: 'x');

        Assert::same($column->getAttributes()['comment'], '');
        Assert::same($column->getAttributes()['note'], 'x');
    }

    public function accumulatesExtraAttributes(): void
    {
        $column = new MySQLColumn('t', 'c');

        $column->integer(foo: 1);
        $column->integer(bar: 2);

        Assert::same($column->getAttributes()['foo'], 1);
        Assert::same($column->getAttributes()['bar'], 2);
    }

    public function listsTheAttributesOfItsType(): void
    {
        $column = new MySQLColumn('t', 'c');
        $column->integer();

        $column->setAttributes(['first' => true]);

        Assert::same($column->getAttributes(), [
            'size' => 11,
            'autoIncrement' => false,
            'unsigned' => false,
            'zerofill' => false,
            'comment' => '',
            'after' => '',
            'first' => true,
            'nullable' => true,
            'defaultValue' => null,
        ]);
    }

    public function namesItselfAfterItsTable(): void
    {
        Assert::same((string) new SQLiteColumn('t', 'c'), 't.c');
    }

    public function describesADecimalForDebugging(): void
    {
        $column = new SQLiteColumn('t', 'c');
        $column->decimal(10, 2);
        $column->defaultValue(1.5);

        Assert::same($column->__debugInfo(), [
            'name' => 'c',
            'type' => ['database' => 'numeric', 'schema' => 'decimal', 'php' => 'float'],
            'nullable' => true,
            'defaultValue' => 1.5,
            'precision' => 10,
            'scale' => 2,
        ]);
    }

    public function describesASizedColumnWithAttributesForDebugging(): void
    {
        $column = new SQLiteColumn('t', 'c');
        $column->string(32);
        $column->nullable(false);
        $column->setAttributes(['foo' => 1]);

        Assert::same($column->__debugInfo(), [
            'name' => 'c',
            'type' => ['database' => 'text', 'schema' => 'text', 'php' => 'string'],
            'size' => 32,
            'attributes' => ['foo' => 1],
        ]);
    }

    public function describesAnEnumForDebugging(): void
    {
        $column = new MySQLColumn('t', 'c');
        $column->enum(['a', 'b']);

        Assert::same($column->__debugInfo()['enumValues'], ['a', 'b']);
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->column = new class('foo', 'bar') extends AbstractColumn {};
    }
}
