<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

//See MySQL Driver!
abstract class DatetimeColumnTest extends BaseTest
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

    #[\Testo\Test]
    public function testTimestampWithNullDefaultAndNullable(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->timestamp('target')->nullable(true)->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testTimestampCurrentTimestamp(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->timestamp('target')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testTimestampCurrentTimestampNotNull(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->timestamp('target')->nullable(false)->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testMultipleTimestampCurrentTimestamp(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->timestamp('target')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->timestamp('target2')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testTimestampDatetimeZero(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->timestamp('target')->defaultValue(0);
        $schema->save();

        $savedSchema = $this->schema('sampleSchema');
        \Testo\Assert::same($savedSchema->column('target')->getDefaultValue()->getTimestamp(), $schema->column('target')->getDefaultValue()->getTimestamp());
    }

    //datetime

    #[\Testo\Test]
    public function testDatetimeWithNullDefaultAndNullable(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->datetime('target')->nullable(true)->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDatetimeCurrentTimestamp(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->datetime('target')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDatetimeCurrentTimestampNotNull(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->datetime('target')->nullable(false)->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testMultipleDatetimeCurrentTimestamp(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->datetime('target')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->datetime('target2')->defaultValue(AbstractColumn::DATETIME_NOW);

        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDatetimeDatetimeWithTimezone(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->datetime('target')->defaultValue(
            new \DateTime('1980-01-01 19:00:00', new \DateTimeZone('UTC')),
        );
        $schema->save();

        $savedSchema = $this->schema('sampleSchema');
        \Testo\Assert::equals($savedSchema->column('target')->getDefaultValue(), $schema->column('target')->getDefaultValue());
    }

    #[\Testo\Test]
    public function testDatetimeDatetimeString(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->datetime('target')->defaultValue('1980-01-01 19:00:00');
        $schema->save();

        $savedSchema = $this->schema('sampleSchema');
        \Testo\Assert::same($savedSchema->column('target')->getDefaultValue()->getTimestamp(), $schema->column('target')->getDefaultValue()->getTimestamp());
    }

    #[\Testo\Test]
    public function testDatetimeDatetimeZero(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->datetime('target')->defaultValue(0);
        $schema->save();

        $savedSchema = $this->schema('sampleSchema');
        \Testo\Assert::same($savedSchema->column('target')->getDefaultValue()->getTimestamp(), $schema->column('target')->getDefaultValue()->getTimestamp());
    }

    //time

    #[\Testo\Test]
    public function testTimeWithNullValue(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->time('target')->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testTimeWithZeroValue(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->time('target')->defaultValue(0);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testTimeWithStringValue(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->time('target')->defaultValue('12:00');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testTimeWithCustomStringValue(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->time('target')->defaultValue('12am');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testTimeWithLongStringValue(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->time('target')->defaultValue('1910-11-10 12am');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDateWithNullValue(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->time('target')->defaultValue(null);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDateWithZeroValue(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->time('target')->defaultValue(0);
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDateWithStringValue(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->time('target')->defaultValue('last friday');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDateWithCustomStringValue(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->time('target')->defaultValue('1910-11-10');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDateWithLongStringValue(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->time('target')->defaultValue('1910-11-10 12am');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDateWithLongStringValueOtherFormat(): void
    {
        $schema = $this->schema('sampleSchema');
        \Testo\Assert::false($schema->exists());

        $schema->time('target')->defaultValue('May 10, 2010');
        $schema->save();

        $this->assertSameAsInDB($schema);
    }
}
