<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\DatetimeColumnTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class DatetimeColumnTest extends CommonClass
{
    public const DRIVER = 'postgres';

    public function testTimestamptz(): void
    {
        $schema = $this->schema('timestamp_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timestamptz('column_name');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('timestamp_tz')->getColumns()['column_name'];
        $this->assertTrue($column->getAttributes()['withTimezone']);
        $this->assertTrue($savedColumn->getAttributes()['withTimezone']);
        $this->assertSame('timestamptz', $column->getAbstractType());
        $this->assertSame(0, $column->getSize());
    }

    public function testTimestampWithTimezone(): void
    {
        $schema = $this->schema('timestamp_with_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timestamp('column_name', withTimezone: true);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('timestamp_with_tz')->getColumns()['column_name'];
        $this->assertTrue($column->getAttributes()['withTimezone']);
        $this->assertTrue($savedColumn->getAttributes()['withTimezone']);
        $this->assertSame('timestamptz', $column->getAbstractType());
    }

    public function testTimeWithTimezone(): void
    {
        $schema = $this->schema('time_with_tz');

        /** @var PostgresColumn $column */
        $column = $schema->time('timetz', withTimezone: true);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('time_with_tz')->getColumns()['timetz'];

        $this->assertTrue($column->getAttributes()['withTimezone']);
        $this->assertTrue($savedColumn->getAttributes()['withTimezone']);
        $this->assertSame('timetz', $column->getAbstractType());
    }

    public function testTimetz(): void
    {
        $schema = $this->schema('time_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timetz('column_name');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('time_tz')->getColumns()['column_name'];

        $this->assertTrue($column->getAttributes()['withTimezone']);
        $this->assertTrue($savedColumn->getAttributes()['withTimezone']);
        $this->assertSame('timetz', $column->getAbstractType());
        $this->assertSame(0, $column->getSize());
    }

    public function testTimestampWithoutTimezone(): void
    {
        $schema = $this->schema('timestamp_without_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timestamp('timestamp');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('timestamp_without_tz')->getColumns()['timestamp'];

        $this->assertFalse($column->getAttributes()['withTimezone']);
        $this->assertFalse($savedColumn->getAttributes()['withTimezone']);
        $this->assertSame('timestamp', $column->getAbstractType());
        $this->assertSame(0, $column->getSize());
    }

    public function testTimeWithoutTimezone(): void
    {
        $schema = $this->schema('time_without_tz');

        /** @var PostgresColumn $column */
        $column = $schema->time('time');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('time_without_tz')->getColumns()['time'];

        $this->assertFalse($column->getAttributes()['withTimezone']);
        $this->assertFalse($savedColumn->getAttributes()['withTimezone']);
        $this->assertSame('time', $column->getAbstractType());
    }

    public function testInterval(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval'];

        $this->assertNull($column->getAttributes()['intervalType']);
        $this->assertNull($savedColumn->getAttributes()['intervalType']);
        $this->assertSame('interval', $column->getAbstractType());
        $this->assertSame(6, $column->getSize());
        $this->assertSame(6, $savedColumn->getSize());
    }

    public function testIntervalWithType(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval_with_type', intervalType: 'YEAR TO MONTH');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval_with_type'];

        $this->assertSame('YEAR TO MONTH', $column->getAttributes()['intervalType']);
        $this->assertSame('YEAR TO MONTH', $savedColumn->getAttributes()['intervalType']);
        $this->assertSame('interval', $column->getAbstractType());
        $this->assertSame(0, $column->getSize());
    }

    public function testIntervalWithPrecision(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval_with_size', size: 6);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval_with_size'];

        $this->assertNull($column->getAttributes()['intervalType']);
        $this->assertNull($savedColumn->getAttributes()['intervalType']);
        $this->assertSame('interval', $column->getAbstractType());
        $this->assertSame(6, $column->getSize());
        $this->assertSame(6, $savedColumn->getSize());
    }

    public function testIntervalWithPrecisionAndUnsupportedPrecisionType(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval_with_size', size: 6, intervalType: 'HOUR TO MINUTE');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval_with_size'];

        $this->assertSame('HOUR TO MINUTE', $column->getAttributes()['intervalType']);
        $this->assertSame('HOUR TO MINUTE', $savedColumn->getAttributes()['intervalType']);
        $this->assertSame('interval', $column->getAbstractType());
        $this->assertSame(0, $column->getSize());
        $this->assertSame(0, $savedColumn->getSize());
    }

    public function testIntervalWithPrecisionAndType(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval_column', intervalType: 'SECOND', size: 6);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval_column'];

        $this->assertSame('SECOND', $column->getAttributes()['intervalType']);
        $this->assertSame('SECOND', $savedColumn->getAttributes()['intervalType']);
        $this->assertSame('interval', $column->getAbstractType());
        $this->assertSame(6, $column->getSize());
        $this->assertSame(6, $savedColumn->getSize());
    }

    public function testExceptionWithInvalidIntervalType(): void
    {
        $schema = $this->schema('interval');

        $schema->interval('interval_column', intervalType: 'foo');

        $this->expectException(SchemaException::class);
        $schema->save();
    }

    public function testDatetime(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->datetime('datetime_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('timestamp', $schema->column('datetime_data')->getInternalType());
        $this->assertSame(0, $schema->column('datetime_data')->getSize());
    }

    public function testDatetimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->datetime('datetime_data', 6);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('timestamp', $schema->column('datetime_data')->getInternalType());
        $this->assertSame(6, $schema->column('datetime_data')->getSize());
    }

    public function testTime(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->time('time_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('time', $schema->column('time_data')->getInternalType());
        $this->assertSame(0, $schema->column('time_data')->getSize());
    }

    public function testTimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->time('time_data', size: 6);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('time', $schema->column('time_data')->getInternalType());
        $this->assertSame(6, $schema->column('time_data')->getSize());
    }

    public function testTimeTzWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->timetz('time_data', size: 6);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('time', $schema->column('time_data')->getInternalType());
        $this->assertSame(6, $schema->column('time_data')->getSize());
    }

    public function testTimestampWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->timestamp('timestamp_data', size: 6);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('timestamp', $schema->column('timestamp_data')->getInternalType());
        $this->assertSame(6, $schema->column('timestamp_data')->getSize());
    }

    public function testTimestampTzWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->timestamptz('timestamp_data', size: 6);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('timestamp', $schema->column('timestamp_data')->getInternalType());
        $this->assertSame(6, $schema->column('timestamp_data')->getSize());
    }
}
