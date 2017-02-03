<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database;

use Spiral\Database\Entities\Database;
use Spiral\Database\Schemas\Prototypes\AbstractTable;

abstract class SchemaConsistencyTest extends BaseTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp()
    {
        $this->database = $this->database();
        $this->database = $this->database();
    }

    public function tearDown()
    {
        $this->dropAll($this->database());
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    public function testPrimary()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->primary('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testBigPrimary()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->bigPrimary('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testInteger()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->integer('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testBigInteger()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->bigInteger('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testString()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->string('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testStringWithLength()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->string('target', 100);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testFloat()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->float('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDouble()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->double('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDecimal_1_0()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->decimal('target', 1, 0);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDecimal_5_2()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->decimal('target', 5, 2);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testBoolean()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->boolean('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testEnum()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->enum('target', ['a', 'b', 'value']);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testJson()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->json('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testTinyInteger()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->tinyInteger('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testText()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->text('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testTinyText()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->tinyText('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testTextLongText()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->longText('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testBinary()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->binary('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testTinyBinary()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->tinyBinary('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testLongBinary()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->longBinary('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testTimestamp()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->timestamp('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDatetime()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->datetime('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDate()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->date('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testTime()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->time('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }
}