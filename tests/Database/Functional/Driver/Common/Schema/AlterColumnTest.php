<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class AlterColumnTest extends BaseTest
{
    public function sampleSchema(string $table): AbstractTable
    {
        $schema = $this->schema($table);

        if (!$schema->exists()) {
            $schema->primary('id');
            $schema->string('first_name')->nullable(false);
            $schema->string('last_name')->nullable(false);
            $schema->string('email', 64)->nullable(false);
            $schema->enum('status', ['active', 'disabled'])->defaultValue('active');
            $schema->double('balance')->defaultValue(0);
            $schema->boolean('flagged')->defaultValue(true);

            $schema->float('floated')->defaultValue(0);

            $schema->text('bio');

            //Some dates
            $schema->timestamp('timestamp')->defaultValue(AbstractColumn::DATETIME_NOW);
            $schema->datetime('datetime')->defaultValue('2017-01-01 00:00:00');
            $schema->date('datetime')->nullable(true);
            $schema->time('datetime')->defaultValue('00:00');
            $schema->datetime('other_datetime')->nullable(true);

            $schema->save(Handler::DO_ALL);
        }

        return $schema;
    }

    //Verification test #1
    #[\Testo\Test]
    public function testSelfComparePreparedSameInstance(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $this->assertSameAsInDB($schema);
    }

    //Verification test #2
    #[\Testo\Test]
    public function testSelfComparePreparedReselected(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testAddColumn(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->string('new_column');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testAddColumnWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->string('new_column')->defaultValue('some_value');
        $schema->save();

        \Testo\Assert::array($schema->string('new_column')->__debugInfo());

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testAddColumnNullable(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->string('new_column')->nullable(true);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testMakeNullable(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        \Testo\Assert::false($this->fetchSchema($schema)->column('first_name')->isNullable());

        $schema->string('first_name')->nullable(true);
        $schema->save();

        $this->assertSameAsInDB($schema);

        \Testo\Assert::true($this->fetchSchema($schema)->column('first_name')->isNullable());
    }

    #[\Testo\Test]
    public function testColumnSizeException(): void
    {
        \Testo\Expect::exception(SchemaException::class);
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->string('first_name', -1);
        $schema->save();
    }

    #[\Testo\Test]
    public function testColumnSize2Exception(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->string('first_name', 256);

        // No limit error
        \Testo\Assert::true(true);
    }

    #[\Testo\Test]
    public function testChangeSize(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        \Testo\Assert::same($this->fetchSchema($schema)->column('first_name')->getSize(), 255);

        $schema->first_name->string(100);
        $schema->save();

        $this->assertSameAsInDB($schema);
        \Testo\Assert::same($this->fetchSchema($schema)->column('first_name')->getSize(), 100);
    }

    #[\Testo\Test]
    public function testDecimalSizes(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->decimal('double_2', 10, 1);
        $schema->save();

        $this->assertSameAsInDB($schema);
        \Testo\Assert::same($this->fetchSchema($schema)->column('double_2')->getPrecision(), 10);
        \Testo\Assert::same($this->fetchSchema($schema)->column('double_2')->getScale(), 1);

        \Testo\Assert::array($schema->decimal('double_2', 10, 1)->__debugInfo());
    }

    #[\Testo\Test]
    public function testDecimalSizesException(): void
    {
        \Testo\Expect::exception(SchemaException::class);
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->decimal('double_2', 0);
    }

    #[\Testo\Test]
    public function testAddColumnNotNullable(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->string('new_column')->nullable(false);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testAddColumnEnum(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue('a');
        $schema->save();

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($this->fetchSchema($schema)->new_column->getEnumValues(), $schema->new_column->getEnumValues());
    }

    #[\Testo\Test]
    public function testChangeEnumValues(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue('a');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $schema->enum('new_column', ['a', 'b', 'c', 'd'])->defaultValue('a');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testEnumOnReserved(): void
    {
        $schema = $this->schema('new_schema');
        \Testo\Assert::false($schema->exists());

        $schema->enum('table', ['a', 'b', 'c'])->defaultValue('a');
        $schema->enum('column', ['a', 'b', 'c']);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeStringToText(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->column('email')->type('text');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeColumnFromIntToFloatWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->integer('balance')->defaultValue(0);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $schema->column('balance')->float();
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeFloatToDoubleWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->column('floated')->type('double');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeDoubleToFloatWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->column('balance')->defaultValue(1);
        $schema->column('balance')->type('float');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeColumnFromIntToStringWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->integer('balance')->defaultValue(0);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $schema->column('balance')->string();
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testAddColumnEnumNullDefault(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeColumnFromEnumToString(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $schema->column('new_column')->string();
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testAddMultipleColumns(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->integer('new_int')->defaultValue(0);
        $schema->integer('new_string_0_default')->defaultValue(0);
        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue('a');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDropColumn(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->dropColumn('first_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDropMultipleColumns(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->dropColumn('first_name');
        $schema->dropColumn('last_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testRenameColumn(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->renameColumn('first_name', 'another_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testRenameThoughtTest(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->column('first_name')->setName('another_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testRenameMultipleColumns(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->renameColumn('first_name', 'another_name');

        //I have no idea what will happen at moment i write this comment
        $schema->renameColumn('last_name', 'first_name');
        //it worked O_o

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeColumnFromNullToNotNull(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->column('first_name')->nullable(false);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeColumnFromNotNullToNull(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->column('flagged')->nullable(true);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testRenameAndDropColumn(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->renameColumn('first_name', 'name');
        $schema->dropColumn('last_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testRenameAndChangeToNotNull(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->renameColumn('first_name', 'name');
        $schema->column('name')->nullable(true);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testRenameAndChangeToNullAndSetNulL(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->renameColumn('flagged', 'name');
        $schema->column('name')->nullable(true);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testRenameAndChangeToNullAndSetNullDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->renameColumn('flagged', 'name');
        $schema->column('name')->nullable(true)->defaultValue(null);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeDefaultValueWithFragment(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->integer('name')->defaultValue(new Fragment('5'));

        $schema->setPrimaryKeys(['id']);
        \Testo\Assert::same($schema->getPrimaryKeys(), ['id']);
        $schema->save();
        $this->assertSameAsInDB($schema);
        \Testo\Assert::same($this->fetchSchema($schema)->column('name')->getDefaultValue(), 5);

        $schema->integer('name')->defaultValue(new Fragment('7'));
        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());

        $this->assertSameAsInDB($schema);
        \Testo\Assert::same($this->fetchSchema($schema)->column('name')->getDefaultValue(), 7);
    }

    #[\Testo\Test]
    public function testAddCustomColumnType(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->type('ltree_column', 'ltree');

        $column = $schema->column('ltree_column');

        \Testo\Assert::same($column->getDeclaredType(), 'ltree');
        \Testo\Assert::same($column->getInternalType(), 'ltree');
        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($column->getAbstractType(), 'unknown');
    }

    #[\Testo\Test]
    public function testCompareShouldIgnoreCustomColumnType(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->type('ltree_column', 'ltree');

        $column = $schema->column('ltree_column');

        $column2 = clone $column;
        $column2->type('other');

        \Testo\Assert::true($column2->compare($column));
    }

    #[\Testo\Test]
    public function testShortcutExceptionIfColumnTypeIsNotExists(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        \Testo\Expect::exception(SchemaException::class);
        $schema->ltree('ltree_column');
    }
}
