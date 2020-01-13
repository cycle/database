<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use Spiral\Database\Database;
use Spiral\Database\Driver\Handler;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractTable;

//See MySQL Driver!
abstract class DatetimeColumnTest extends BaseTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp(): void
    {
        $this->database = $this->db();
    }

    public function tearDown(): void
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

    //timestamp

    public function testTimestampWithNullDefaultAndNullable(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->timestamp('target')->nullable(true)->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testTimestampCurrentTimestamp(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->timestamp('target')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testTimestampCurrentTimestampNotNull(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->timestamp('target')->nullable(false)->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testMultipleTimestampCurrentTimestamp(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->timestamp('target')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->timestamp('target2')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testTimestampDatetimeZero(): void
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

    public function testDatetimeWithNullDefaultAndNullable(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->datetime('target')->nullable(true)->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDatetimeCurrentTimestamp(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->datetime('target')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDatetimeCurrentTimestampNotNull(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->datetime('target')->nullable(false)->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testMultipleDatetimeCurrentTimestamp(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->datetime('target')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->datetime('target2')->defaultValue(AbstractColumn::DATETIME_NOW);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDatetimeDatetimeWithTimezone(): void
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

    public function testDatetimeDatetimeString(): void
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

    public function testDatetimeDatetimeZero(): void
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

    public function testTimeWithNullValue(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testTimeWithZeroValue(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue(0);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testTimeWithStringValue(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue('12:00');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testTimeWithCustomStringValue(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue('12am');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testTimeWithLongStringValue(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue('1910-11-10 12am');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDateWithNullValue(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDateWithZeroValue(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue(0);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDateWithStringValue(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue('last friday');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDateWithCustomStringValue(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue('1910-11-10');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDateWithLongStringValue(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue('1910-11-10 12am');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    public function testDateWithLongStringValueOtherFormat(): void
    {
        $schema = $this->schema('sampleSchema');
        $this->assertFalse($schema->exists());

        $schema->time('target')->defaultValue('May 10, 2010');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }
}
