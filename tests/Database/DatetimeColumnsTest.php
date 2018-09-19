<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Database\Tests;

use Spiral\Database\Entity\AbstractHandler;
use Spiral\Database\Entity\Database;
use Spiral\Database\Schema\Prototypes\AbstractColumn;
use Spiral\Database\Schema\Prototypes\AbstractTable;

//See MySQL Driver!
abstract class DatetimeColumnsTest extends BaseTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp()
    {
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

    //timestamp

    public function testTimestampWithNullDefaultAndNullable()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->timestamp('target')->nullable(true)->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testTimestampCurrentTimestamp()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->timestamp('target')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testTimestampCurrentTimestampNotNull()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->timestamp('target')->nullable(false)->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testMultipleTimestampCurrentTimestamp()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->timestamp('target')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->timestamp('target2')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testTimestampDatetimeZero()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->timestamp('target')->defaultValue(0);
        $schema->save();

        $savedSchema = $this->schema('sampleSchema');
        $this->assertSame(
            $schema->column('target')->getDefaultValue()->getTimestamp(),
            $savedSchema->column('target')->getDefaultValue()->getTimestamp()
        );
    }

    //datetime

    public function testDatetimeWithNullDefaultAndNullable()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->datetime('target')->nullable(true)->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDatetimeCurrentTimestamp()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->datetime('target')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDatetimeCurrentTimestampNotNull()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->datetime('target')->nullable(false)->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testMultipleDatetimeCurrentTimestamp()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->datetime('target')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->datetime('target2')->defaultValue(AbstractColumn::DATETIME_NOW);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDatetimeDatetimeWithTimezone()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->datetime('target')->defaultValue(
            new \DateTime('1980-01-01 19:00:00', new \DateTimeZone('UTC'))
        );
        $schema->save();

        $savedSchema = $this->schema('sampleSchema');
        $this->assertEquals(
            $schema->column('target')->getDefaultValue(),
            $savedSchema->column('target')->getDefaultValue()
        );
    }

    public function testDatetimeDatetimeString()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->datetime('target')->defaultValue('1980-01-01 19:00:00');
        $schema->save();

        $savedSchema = $this->schema('sampleSchema');
        $this->assertSame(
            $schema->column('target')->getDefaultValue()->getTimestamp(),
            $savedSchema->column('target')->getDefaultValue()->getTimestamp()
        );
    }

    public function testDatetimeDatetimeZero()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->datetime('target')->defaultValue(0);
        $schema->save();

        $savedSchema = $this->schema('sampleSchema');
        $this->assertSame(
            $schema->column('target')->getDefaultValue()->getTimestamp(),
            $savedSchema->column('target')->getDefaultValue()->getTimestamp()
        );
    }

    //time

    public function testTimeWithNullValue()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testTimeWithZeroValue()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue(0);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testTimeWithStringValue()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue('12:00');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testTimeWithCustomStringValue()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue('12am');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testTimeWithLongStringValue()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue('1910-11-10 12am');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    //date

    public function testDateWithNullValue()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDateWithZeroValue()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue(0);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDateWithStringValue()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue('last friday');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDateWithCustomStringValue()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue('1910-11-10');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDateWithLongStringValue()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue('1910-11-10 12am');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDateWithLongStringValueOtherFormat()
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue('May 10, 2010');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }
}