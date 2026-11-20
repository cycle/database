<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\DatetimeColumnTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class DatetimeColumnTest extends CommonClass
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testTimestamptz(): void
    {
        $schema = $this->schema('timestamp_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timestamptz('column_name');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('timestamp_tz')->getColumns()['column_name'];
        \Testo\Assert::true($column->getAttributes()['withTimezone']);
        \Testo\Assert::true($savedColumn->getAttributes()['withTimezone']);
        \Testo\Assert::same($column->getAbstractType(), 'timestamptz');
        \Testo\Assert::same($column->getSize(), 0);
    }

    #[\Testo\Test]
    public function testTimestampWithTimezone(): void
    {
        $schema = $this->schema('timestamp_with_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timestamp('column_name', withTimezone: true);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('timestamp_with_tz')->getColumns()['column_name'];
        \Testo\Assert::true($column->getAttributes()['withTimezone']);
        \Testo\Assert::true($savedColumn->getAttributes()['withTimezone']);
        \Testo\Assert::same($column->getAbstractType(), 'timestamptz');
    }

    #[\Testo\Test]
    public function testTimeWithTimezone(): void
    {
        $schema = $this->schema('time_with_tz');

        /** @var PostgresColumn $column */
        $column = $schema->time('timetz', withTimezone: true);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('time_with_tz')->getColumns()['timetz'];

        \Testo\Assert::true($column->getAttributes()['withTimezone']);
        \Testo\Assert::true($savedColumn->getAttributes()['withTimezone']);
        \Testo\Assert::same($column->getAbstractType(), 'timetz');
    }

    #[\Testo\Test]
    public function testTimetz(): void
    {
        $schema = $this->schema('time_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timetz('column_name');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('time_tz')->getColumns()['column_name'];

        \Testo\Assert::true($column->getAttributes()['withTimezone']);
        \Testo\Assert::true($savedColumn->getAttributes()['withTimezone']);
        \Testo\Assert::same($column->getAbstractType(), 'timetz');
        \Testo\Assert::same($column->getSize(), 0);
    }

    #[\Testo\Test]
    public function testTimestampWithoutTimezone(): void
    {
        $schema = $this->schema('timestamp_without_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timestamp('timestamp');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('timestamp_without_tz')->getColumns()['timestamp'];

        \Testo\Assert::false($column->getAttributes()['withTimezone']);
        \Testo\Assert::false($savedColumn->getAttributes()['withTimezone']);
        \Testo\Assert::same($column->getAbstractType(), 'timestamp');
        \Testo\Assert::same($column->getSize(), 0);
    }

    #[\Testo\Test]
    public function testTimeWithoutTimezone(): void
    {
        $schema = $this->schema('time_without_tz');

        /** @var PostgresColumn $column */
        $column = $schema->time('time');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('time_without_tz')->getColumns()['time'];

        \Testo\Assert::false($column->getAttributes()['withTimezone']);
        \Testo\Assert::false($savedColumn->getAttributes()['withTimezone']);
        \Testo\Assert::same($column->getAbstractType(), 'time');
    }

    #[\Testo\Test]
    public function testInterval(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval'];

        \Testo\Assert::null($column->getAttributes()['intervalType']);
        \Testo\Assert::null($savedColumn->getAttributes()['intervalType']);
        \Testo\Assert::same($column->getAbstractType(), 'interval');
        \Testo\Assert::same($column->getSize(), 6);
        \Testo\Assert::same($savedColumn->getSize(), 6);
    }

    #[\Testo\Test]
    public function testIntervalWithType(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval_with_type', intervalType: 'YEAR TO MONTH');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval_with_type'];

        \Testo\Assert::same($column->getAttributes()['intervalType'], 'YEAR TO MONTH');
        \Testo\Assert::same($savedColumn->getAttributes()['intervalType'], 'YEAR TO MONTH');
        \Testo\Assert::same($column->getAbstractType(), 'interval');
        \Testo\Assert::same($column->getSize(), 0);
    }

    #[\Testo\Test]
    public function testIntervalWithPrecision(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval_with_size', size: 6);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval_with_size'];

        \Testo\Assert::null($column->getAttributes()['intervalType']);
        \Testo\Assert::null($savedColumn->getAttributes()['intervalType']);
        \Testo\Assert::same($column->getAbstractType(), 'interval');
        \Testo\Assert::same($column->getSize(), 6);
        \Testo\Assert::same($savedColumn->getSize(), 6);
    }

    #[\Testo\Test]
    public function testIntervalWithPrecisionAndUnsupportedPrecisionType(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval_with_size', size: 6, intervalType: 'HOUR TO MINUTE');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval_with_size'];

        \Testo\Assert::same($column->getAttributes()['intervalType'], 'HOUR TO MINUTE');
        \Testo\Assert::same($savedColumn->getAttributes()['intervalType'], 'HOUR TO MINUTE');
        \Testo\Assert::same($column->getAbstractType(), 'interval');
        \Testo\Assert::same($column->getSize(), 0);
        \Testo\Assert::same($savedColumn->getSize(), 0);
    }

    #[\Testo\Test]
    public function testIntervalWithPrecisionAndType(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval_column', intervalType: 'SECOND', size: 6);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval_column'];

        \Testo\Assert::same($column->getAttributes()['intervalType'], 'SECOND');
        \Testo\Assert::same($savedColumn->getAttributes()['intervalType'], 'SECOND');
        \Testo\Assert::same($column->getAbstractType(), 'interval');
        \Testo\Assert::same($column->getSize(), 6);
        \Testo\Assert::same($savedColumn->getSize(), 6);
    }

    #[\Testo\Test]
    public function testExceptionWithInvalidIntervalType(): void
    {
        $schema = $this->schema('interval');

        $schema->interval('interval_column', intervalType: 'foo');

        \Testo\Expect::exception(SchemaException::class);
        $schema->save();
    }

    #[\Testo\Test]
    public function testDatetime(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->datetime('datetime_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('datetime_data')->getInternalType(), 'timestamp');
        \Testo\Assert::same($schema->column('datetime_data')->getSize(), 0);
    }

    #[\Testo\Test]
    public function testDatetimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->datetime('datetime_data', 6);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('datetime_data')->getInternalType(), 'timestamp');
        \Testo\Assert::same($schema->column('datetime_data')->getSize(), 6);
    }

    #[\Testo\Test]
    public function testTime(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->time('time_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('time_data')->getInternalType(), 'time');
        \Testo\Assert::same($schema->column('time_data')->getSize(), 0);
    }

    #[\Testo\Test]
    public function testTimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->time('time_data', size: 6);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('time_data')->getInternalType(), 'time');
        \Testo\Assert::same($schema->column('time_data')->getSize(), 6);
    }

    #[\Testo\Test]
    public function testTimeTzWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->timetz('time_data', size: 6);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('time_data')->getInternalType(), 'time');
        \Testo\Assert::same($schema->column('time_data')->getSize(), 6);
    }

    #[\Testo\Test]
    public function testTimestampWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->timestamp('timestamp_data', size: 6);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('timestamp_data')->getInternalType(), 'timestamp');
        \Testo\Assert::same($schema->column('timestamp_data')->getSize(), 6);
    }

    #[\Testo\Test]
    public function testTimestampTzWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->timestamptz('timestamp_data', size: 6);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('timestamp_data')->getInternalType(), 'timestamp');
        \Testo\Assert::same($schema->column('timestamp_data')->getSize(), 6);
    }
}
