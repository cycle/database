<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

abstract class AlterColumnTest extends DatabaseTestCase
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
    #[Test]
    public function testSelfComparePreparedSameInstance(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        SchemaAssert::sameAsInDb($schema);
    }

    //Verification test #2
    #[Test]
    public function testSelfComparePreparedReselected(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testAddColumn(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->string('new_column');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testAddColumnWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->string('new_column')->defaultValue('some_value');
        $schema->save();

        Assert::array($schema->string('new_column')->__debugInfo());

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testAddColumnNullable(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->string('new_column')->nullable(true);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testMakeNullable(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        Assert::false($this->fetchSchema($schema)->column('first_name')->isNullable());

        $schema->string('first_name')->nullable(true);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        Assert::true($this->fetchSchema($schema)->column('first_name')->isNullable());
    }

    #[Test]
    public function testColumnSizeException(): void
    {
        Expect::exception(SchemaException::class);
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->string('first_name', -1);
        $schema->save();
    }

    #[Test]
    public function testColumnSize2Exception(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->string('first_name', 256);

        // No limit error
        Assert::true(true);
    }

    #[Test]
    public function testChangeSize(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        Assert::same($this->fetchSchema($schema)->column('first_name')->getSize(), 255);

        $schema->first_name->string(100);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
        Assert::same($this->fetchSchema($schema)->column('first_name')->getSize(), 100);
    }

    #[Test]
    public function testDecimalSizes(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->decimal('double_2', 10, 1);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
        Assert::same($this->fetchSchema($schema)->column('double_2')->getPrecision(), 10);
        Assert::same($this->fetchSchema($schema)->column('double_2')->getScale(), 1);

        Assert::array($schema->decimal('double_2', 10, 1)->__debugInfo());
    }

    #[Test]
    public function testDecimalSizesException(): void
    {
        Expect::exception(SchemaException::class);
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->decimal('double_2', 0);
    }

    #[Test]
    public function testAddColumnNotNullable(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->string('new_column')->nullable(false);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testAddColumnEnum(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue('a');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        Assert::same($this->fetchSchema($schema)->new_column->getEnumValues(), $schema->new_column->getEnumValues());
    }

    #[Test]
    public function testChangeEnumValues(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue('a');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $schema->enum('new_column', ['a', 'b', 'c', 'd'])->defaultValue('a');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testEnumOnReserved(): void
    {
        $schema = $this->schema('new_schema');
        Assert::false($schema->exists());

        $schema->enum('table', ['a', 'b', 'c'])->defaultValue('a');
        $schema->enum('column', ['a', 'b', 'c']);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testChangeStringToText(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->column('email')->type('text');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testChangeColumnFromIntToFloatWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->integer('balance')->defaultValue(0);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $schema->column('balance')->float();
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testChangeFloatToDoubleWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->column('floated')->type('double');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testChangeDoubleToFloatWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->column('balance')->defaultValue(1);
        $schema->column('balance')->type('float');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testChangeColumnFromIntToStringWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->integer('balance')->defaultValue(0);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $schema->column('balance')->string();
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testAddColumnEnumNullDefault(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue(null);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testChangeColumnFromEnumToString(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue(null);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $schema->column('new_column')->string();
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testAddMultipleColumns(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->integer('new_int')->defaultValue(0);
        $schema->integer('new_string_0_default')->defaultValue(0);
        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue('a');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testDropColumn(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->dropColumn('first_name');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testDropMultipleColumns(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->dropColumn('first_name');
        $schema->dropColumn('last_name');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testRenameColumn(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->renameColumn('first_name', 'another_name');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testRenameThoughtTest(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->column('first_name')->setName('another_name');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testRenameMultipleColumns(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->renameColumn('first_name', 'another_name');

        //I have no idea what will happen at moment i write this comment
        $schema->renameColumn('last_name', 'first_name');
        //it worked O_o

        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testChangeColumnFromNullToNotNull(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->column('first_name')->nullable(false);

        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testChangeColumnFromNotNullToNull(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->column('flagged')->nullable(true);

        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testRenameAndDropColumn(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->renameColumn('first_name', 'name');
        $schema->dropColumn('last_name');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testRenameAndChangeToNotNull(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->renameColumn('first_name', 'name');
        $schema->column('name')->nullable(true);

        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testRenameAndChangeToNullAndSetNulL(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->renameColumn('flagged', 'name');
        $schema->column('name')->nullable(true);

        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testRenameAndChangeToNullAndSetNullDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->renameColumn('flagged', 'name');
        $schema->column('name')->nullable(true)->defaultValue(null);

        $schema->save();

        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testChangeDefaultValueWithFragment(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $schema->primary('id');
        $schema->integer('name')->defaultValue(new Fragment('5'));

        $schema->setPrimaryKeys(['id']);
        Assert::same($schema->getPrimaryKeys(), ['id']);
        $schema->save();
        SchemaAssert::sameAsInDb($schema);
        Assert::same($this->fetchSchema($schema)->column('name')->getDefaultValue(), 5);

        $schema->integer('name')->defaultValue(new Fragment('7'));
        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        SchemaAssert::sameAsInDb($schema);
        Assert::same($this->fetchSchema($schema)->column('name')->getDefaultValue(), 7);
    }

    #[Test]
    public function testAddCustomColumnType(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->type('ltree_column', 'ltree');

        $column = $schema->column('ltree_column');

        Assert::same($column->getDeclaredType(), 'ltree');
        Assert::same($column->getInternalType(), 'ltree');
        Assert::same($column->getType(), 'string');
        Assert::same($column->getAbstractType(), 'unknown');
    }

    #[Test]
    public function testCompareShouldIgnoreCustomColumnType(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->type('ltree_column', 'ltree');

        $column = $schema->column('ltree_column');

        $column2 = clone $column;
        $column2->type('other');

        Assert::true($column2->compare($column));
    }

    #[Test]
    public function testShortcutExceptionIfColumnTypeIsNotExists(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        Expect::exception(SchemaException::class);
        $schema->ltree('ltree_column');
    }
}
