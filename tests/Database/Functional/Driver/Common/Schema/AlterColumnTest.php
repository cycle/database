<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Exception\SchemaException;
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

            $schema->save(Handler::DO_ALL);
        }

        return $schema;
    }

    //Verification test #1
    public function testSelfComparePreparedSameInstance(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $this->assertSameAsInDB($schema);
    }

    //Verification test #2
    public function testSelfComparePreparedReselected(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $this->assertSameAsInDB($schema);
    }

    public function testAddColumn(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->string('new_column');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testAddColumnWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->string('new_column')->defaultValue('some_value');
        $schema->save();

        $this->assertIsArray($schema->string('new_column')->__debugInfo());

        $this->assertSameAsInDB($schema);
    }

    public function testAddColumnNullable(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->string('new_column')->nullable(true);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testMakeNullable(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $this->assertFalse($this->fetchSchema($schema)->column('first_name')->isNullable());

        $schema->string('first_name')->nullable(true);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $this->assertTrue($this->fetchSchema($schema)->column('first_name')->isNullable());
    }

    public function testColumnSizeException(): void
    {
        $this->expectException(SchemaException::class);
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->string('first_name', -1);
        $schema->save();
    }

    public function testColumnSize2Exception(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->string('first_name', 256);

        // No limit error
        $this->assertTrue(true);
    }

    public function testChangeSize(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $this->assertSame(255, $this->fetchSchema($schema)->column('first_name')->getSize());

        $schema->first_name->string(100);
        $schema->save();

        $this->assertSameAsInDB($schema);
        $this->assertSame(100, $this->fetchSchema($schema)->column('first_name')->getSize());
    }

    public function testDecimalSizes(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->decimal('double_2', 10, 1);
        $schema->save();

        $this->assertSameAsInDB($schema);
        $this->assertSame(10, $this->fetchSchema($schema)->column('double_2')->getPrecision());
        $this->assertSame(1, $this->fetchSchema($schema)->column('double_2')->getScale());

        $this->assertIsArray($schema->decimal('double_2', 10, 1)->__debugInfo());
    }

    public function testDecimalSizesException(): void
    {
        $this->expectException(SchemaException::class);
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->decimal('double_2', 0);
    }

    public function testAddColumnNotNullable(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->string('new_column')->nullable(false);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testAddColumnEnum(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue('a');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $this->assertSame(
            $schema->new_column->getEnumValues(),
            $this->fetchSchema($schema)->new_column->getEnumValues()
        );
    }

    public function testChangeEnumValues(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue('a');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $schema->enum('new_column', ['a', 'b', 'c', 'd'])->defaultValue('a');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testEnumOnReserved(): void
    {
        $schema = $this->schema('new_schema');
        $this->assertFalse($schema->exists());

        $schema->enum('table', ['a', 'b', 'c'])->defaultValue('a');
        $schema->enum('column', ['a', 'b', 'c']);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeStringToText(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->column('email')->type('text');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeColumnFromIntToFloatWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->integer('balance')->defaultValue(0);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $schema->column('balance')->float();
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeFloatToDoubleWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->column('floated')->type('double');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeDoubleToFloatWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->column('balance')->defaultValue(1);
        $schema->column('balance')->type('float');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeColumnFromIntToStringWithDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->integer('balance')->defaultValue(0);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $schema->column('balance')->string();
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testAddColumnEnumNullDefault(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeColumnFromEnumToString(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $schema->column('new_column')->string();
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testAddMultipleColumns(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->integer('new_int')->defaultValue(0);
        $schema->integer('new_string_0_default')->defaultValue(0);
        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue('a');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDropColumn(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->dropColumn('first_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDropMultipleColumns(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->dropColumn('first_name');
        $schema->dropColumn('last_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testRenameColumn(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->renameColumn('first_name', 'another_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testRenameThoughtTest(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->column('first_name')->setName('another_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testRenameMultipleColumns(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->renameColumn('first_name', 'another_name');

        //I have no idea what will happen at moment i write this comment
        $schema->renameColumn('last_name', 'first_name');
        //it worked O_o

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeColumnFromNullToNotNull(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->column('first_name')->nullable(false);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeColumnFromNotNullToNull(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->column('flagged')->nullable(true);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testRenameAndDropColumn(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->renameColumn('first_name', 'name');
        $schema->dropColumn('last_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testRenameAndChangeToNotNull(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->renameColumn('first_name', 'name');
        $schema->column('name')->nullable(true);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testRenameAndChangeToNullAndSetNulL(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->renameColumn('flagged', 'name');
        $schema->column('name')->nullable(true);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testRenameAndChangeToNullAndSetNullDefaultValue(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->renameColumn('flagged', 'name');
        $schema->column('name')->nullable(true)->defaultValue(null);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }
}
