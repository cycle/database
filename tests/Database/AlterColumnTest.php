<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests;

use Spiral\Database\Database;
use Spiral\Database\Driver\AbstractHandler;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractTable;

abstract class AlterColumnTest extends BaseTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp()
    {
        $this->database = $this->db();
    }

    public function tearDown()
    {
        $this->dropDatabase($this->db());
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

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

            $schema->save(AbstractHandler::DO_ALL);
        }

        return $schema;
    }

    //Verification test #1
    public function testSelfComparePreparedSameInstance()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $this->assertSameAsInDB($schema);
    }

    //Verification test #2
    public function testSelfComparePreparedReselected()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $this->assertSameAsInDB($schema);
    }

    public function testAddColumn()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->string('new_column');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testAddColumnWithDefaultValue()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->string('new_column')->defaultValue('some_value');
        $schema->save();

        $this->assertInternalType('array', $schema->string('new_column')->__debugInfo());

        $this->assertSameAsInDB($schema);
    }

    public function testAddColumnNullable()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->string('new_column')->nullable(true);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testMakeNullable()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $this->assertFalse($this->fetchSchema($schema)->column('first_name')->isNullable());

        $schema->string('first_name')->nullable(true);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $this->assertTrue($this->fetchSchema($schema)->column('first_name')->isNullable());
    }

    /**
     * @expectedException \Spiral\Database\Exception\SchemaException
     */
    public function testColumnSizeException()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->string('first_name', -1);
        $schema->save();
    }

    /**
     * @expectedException \Spiral\Database\Exception\SchemaException
     */
    public function testColumnSize2Exception()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->string('first_name', 256);
        $schema->save();
    }

    public function testChangeSize()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $this->assertSame(255, $this->fetchSchema($schema)->column('first_name')->getSize());

        $schema->string('first_name', 100);
        $schema->save();

        $this->assertSameAsInDB($schema);
        $this->assertSame(100, $this->fetchSchema($schema)->column('first_name')->getSize());
    }

    public function testDecimalSizes()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->decimal('double_2', 10, 1);
        $schema->save();

        $this->assertSameAsInDB($schema);
        $this->assertSame(10, $this->fetchSchema($schema)->column('double_2')->getPrecision());
        $this->assertSame(1, $this->fetchSchema($schema)->column('double_2')->getScale());
    }

    public function testAddColumnNotNullable()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->string('new_column')->nullable(false);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testAddColumnEnum()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->nullable('a');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeEnumValues()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->nullable('a');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $schema->enum('new_column', ['a', 'b', 'c', 'd'])->nullable('a');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeStringToText()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->column('email')->type('text');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeColumnFromIntToFloatWithDefaultValue()
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

    public function testChangeFloatToDoubleWithDefaultValue()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->column('floated')->type('double');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeDoubleToFloatWithDefaultValue()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->column('balance')->defaultValue(1);
        $schema->column('balance')->type('float');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeColumnFromIntToStringWithDefaultValue()
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

    public function testAddColumnEnumNullDefault()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->enum('new_column', ['a', 'b', 'c'])->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeColumnFromEnumToString()
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

    public function testAddMultipleColumns()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->integer('new_int')->defaultValue(0);
        $schema->integer('new_string_0_default')->defaultValue(0);
        $schema->enum('new_column', ['a', 'b', 'c'])->nullable('a');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDropColumn()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->dropColumn('first_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDropMultipleColumns()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->dropColumn('first_name');
        $schema->dropColumn('last_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testRenameColumn()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->renameColumn('first_name', 'another_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testRenameThoughtTest()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->column('first_name')->setName('another_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testRenameMultipleColumns()
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

    public function testChangeColumnFromNullToNotNull()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->column('first_name')->nullable(false);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testChangeColumnFromNotNullToNull()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->column('flagged')->nullable(true);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testRenameAndDropColumn()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->renameColumn('first_name', 'name');
        $schema->dropColumn('last_name');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testRenameAndChangeToNotNull()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->renameColumn('first_name', 'name');
        $schema->column('name')->nullable(true);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testRenameAndChangeToNullAndSetNulL()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->renameColumn('flagged', 'name');
        $schema->column('name')->nullable(true);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testRenameAndChangeToNullAndSetNullDefaultValue()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->renameColumn('flagged', 'name');
        $schema->column('name')->nullable(true)->defaultValue(null);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }
}