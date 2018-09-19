<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Database\Tests;

use Spiral\Database\Database;
use Spiral\Database\Schema\AbstractTable;

/**
 * @todo need more validations and test for:
 *       - binary, non empty string
 *       - binary, empty string
 *       - enum invalid value
 *       - decimal, invalid value
 *       - string, too long default value
 */
abstract class DefaultValuesTest extends BaseTest
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

    public function testDefaultNullValueForInteger()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->integer('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
        $this->assertNull($schema->column('target')->getDefaultValue());
    }

    public function testDefaultPositiveValueForInteger()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->integer('target')->defaultValue(mt_rand(0, 100000));

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    //Hahahaa, Postgres 9.4 and lower
    public function testDefaultNegativeValueForInteger()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->integer('target')->defaultValue(mt_rand(-100000, 0));

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDefaultNullValueForFloat()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->float('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
        $this->assertNull($schema->column('target')->getDefaultValue());
    }

    public function testDefaultPositiveValueForFloat()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->float('target')->defaultValue(mt_rand(0, 100000));

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDefaultNegativeValueForFloat()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->float('target')->defaultValue(mt_rand(-100000, 0));

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDefaultNullValueForDouble()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->double('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
        $this->assertNull($schema->column('target')->getDefaultValue());
    }

    public function testDefaultPositiveValueForDouble()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->double('target')->defaultValue(mt_rand(0, 100000));

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDefaultNegativeValueForDouble()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->double('target')->defaultValue(mt_rand(-100000, 0));

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDefaultNullValueForDecimal()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->decimal('target', 10, 10)->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
        $this->assertNull($schema->column('target')->getDefaultValue());
    }

    public function testDefaultPositiveValueForDecimal()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        //@todo test with invalid default value when code is ready
        $column = $schema->decimal('target', 10, 1)->defaultValue(mt_rand(0, 100000));

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDefaultNegativeValueForDecimal()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        //@todo test with invalid default value when code is ready
        $column = $schema->decimal('target', 10, 1)->defaultValue(mt_rand(-100000, 0));

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDefaultNullForBoolean()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->boolean('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
        $this->assertNull($schema->column('target')->getDefaultValue());
    }

    public function testDefaultTrueForBoolean()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->boolean('target')->defaultValue(true);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDefaultFalseForBoolean()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->boolean('target')->defaultValue(false);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testStringDefaultValueEmpty()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->string('target')->defaultValue('');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testStringDefaultValueNull()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->string('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
        $this->assertNull($schema->column('target')->getDefaultValue());
    }

    public function testStringDefaultValueString()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->string('target')->defaultValue('string');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    //no tests for longText, tinyText due all similar
    public function testTextDefaultValueEmpty()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->text('target')->defaultValue('');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testTextDefaultValueNull()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->text('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
        $this->assertNull($schema->column('target')->getDefaultValue());
    }

    public function testTextDefaultValueString()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        //This WILL fail in MySQL!
        $column = $schema->text('target')->defaultValue('non empty');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testEnumDefaultValueNull()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->enum('target', ['a', 'b', 'c'])->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
        $this->assertNull($schema->column('target')->getDefaultValue());
    }

    public function testEnumDefaultValueValid()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->enum('target', ['a', 'b', 'c'])->defaultValue('a');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }
}
