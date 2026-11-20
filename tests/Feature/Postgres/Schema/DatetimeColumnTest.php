<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Tests\Feature\Common\Schema\DatetimeColumnTest as CommonClass;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[OnlyDriver(Driver::Postgres)]
final class DatetimeColumnTest extends CommonClass
{
    #[Test]
    public function testTimestamptz(): void
    {
        $schema = $this->schema('timestamp_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timestamptz('column_name');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('timestamp_tz')->getColumns()['column_name'];
        Assert::true($column->getAttributes()['withTimezone']);
        Assert::true($savedColumn->getAttributes()['withTimezone']);
        Assert::same($column->getAbstractType(), 'timestamptz');
        Assert::same($column->getSize(), 0);
    }

    #[Test]
    public function testTimestampWithTimezone(): void
    {
        $schema = $this->schema('timestamp_with_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timestamp('column_name', withTimezone: true);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('timestamp_with_tz')->getColumns()['column_name'];
        Assert::true($column->getAttributes()['withTimezone']);
        Assert::true($savedColumn->getAttributes()['withTimezone']);
        Assert::same($column->getAbstractType(), 'timestamptz');
    }

    #[Test]
    public function testTimeWithTimezone(): void
    {
        $schema = $this->schema('time_with_tz');

        /** @var PostgresColumn $column */
        $column = $schema->time('timetz', withTimezone: true);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('time_with_tz')->getColumns()['timetz'];

        Assert::true($column->getAttributes()['withTimezone']);
        Assert::true($savedColumn->getAttributes()['withTimezone']);
        Assert::same($column->getAbstractType(), 'timetz');
    }

    #[Test]
    public function testTimetz(): void
    {
        $schema = $this->schema('time_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timetz('column_name');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('time_tz')->getColumns()['column_name'];

        Assert::true($column->getAttributes()['withTimezone']);
        Assert::true($savedColumn->getAttributes()['withTimezone']);
        Assert::same($column->getAbstractType(), 'timetz');
        Assert::same($column->getSize(), 0);
    }

    #[Test]
    public function testTimestampWithoutTimezone(): void
    {
        $schema = $this->schema('timestamp_without_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timestamp('timestamp');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('timestamp_without_tz')->getColumns()['timestamp'];

        Assert::false($column->getAttributes()['withTimezone']);
        Assert::false($savedColumn->getAttributes()['withTimezone']);
        Assert::same($column->getAbstractType(), 'timestamp');
        Assert::same($column->getSize(), 0);
    }

    #[Test]
    public function testTimeWithoutTimezone(): void
    {
        $schema = $this->schema('time_without_tz');

        /** @var PostgresColumn $column */
        $column = $schema->time('time');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('time_without_tz')->getColumns()['time'];

        Assert::false($column->getAttributes()['withTimezone']);
        Assert::false($savedColumn->getAttributes()['withTimezone']);
        Assert::same($column->getAbstractType(), 'time');
    }

    #[Test]
    public function testInterval(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval'];

        Assert::null($column->getAttributes()['intervalType']);
        Assert::null($savedColumn->getAttributes()['intervalType']);
        Assert::same($column->getAbstractType(), 'interval');
        Assert::same($column->getSize(), 6);
        Assert::same($savedColumn->getSize(), 6);
    }

    #[Test]
    public function testIntervalWithType(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval_with_type', intervalType: 'YEAR TO MONTH');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval_with_type'];

        Assert::same($column->getAttributes()['intervalType'], 'YEAR TO MONTH');
        Assert::same($savedColumn->getAttributes()['intervalType'], 'YEAR TO MONTH');
        Assert::same($column->getAbstractType(), 'interval');
        Assert::same($column->getSize(), 0);
    }

    #[Test]
    public function testIntervalWithPrecision(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval_with_size', size: 6);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval_with_size'];

        Assert::null($column->getAttributes()['intervalType']);
        Assert::null($savedColumn->getAttributes()['intervalType']);
        Assert::same($column->getAbstractType(), 'interval');
        Assert::same($column->getSize(), 6);
        Assert::same($savedColumn->getSize(), 6);
    }

    #[Test]
    public function testIntervalWithPrecisionAndUnsupportedPrecisionType(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval_with_size', size: 6, intervalType: 'HOUR TO MINUTE');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval_with_size'];

        Assert::same($column->getAttributes()['intervalType'], 'HOUR TO MINUTE');
        Assert::same($savedColumn->getAttributes()['intervalType'], 'HOUR TO MINUTE');
        Assert::same($column->getAbstractType(), 'interval');
        Assert::same($column->getSize(), 0);
        Assert::same($savedColumn->getSize(), 0);
    }

    #[Test]
    public function testIntervalWithPrecisionAndType(): void
    {
        $schema = $this->schema('interval');

        $column = $schema->interval('interval_column', intervalType: 'SECOND', size: 6);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('interval')->getColumns()['interval_column'];

        Assert::same($column->getAttributes()['intervalType'], 'SECOND');
        Assert::same($savedColumn->getAttributes()['intervalType'], 'SECOND');
        Assert::same($column->getAbstractType(), 'interval');
        Assert::same($column->getSize(), 6);
        Assert::same($savedColumn->getSize(), 6);
    }

    #[Test]
    public function testExceptionWithInvalidIntervalType(): void
    {
        $schema = $this->schema('interval');

        $schema->interval('interval_column', intervalType: 'foo');

        Expect::exception(SchemaException::class);
        $schema->save();
    }

    #[Test]
    public function testDatetime(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->datetime('datetime_data');
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('datetime_data')->getInternalType(), 'timestamp');
        Assert::same($schema->column('datetime_data')->getSize(), 0);
    }

    #[Test]
    public function testDatetimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->datetime('datetime_data', 6);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('datetime_data')->getInternalType(), 'timestamp');
        Assert::same($schema->column('datetime_data')->getSize(), 6);
    }

    #[Test]
    public function testTime(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->time('time_data');
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('time_data')->getInternalType(), 'time');
        Assert::same($schema->column('time_data')->getSize(), 0);
    }

    #[Test]
    public function testTimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->time('time_data', size: 6);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('time_data')->getInternalType(), 'time');
        Assert::same($schema->column('time_data')->getSize(), 6);
    }

    #[Test]
    public function testTimeTzWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->timetz('time_data', size: 6);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('time_data')->getInternalType(), 'time');
        Assert::same($schema->column('time_data')->getSize(), 6);
    }

    #[Test]
    public function testTimestampWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->timestamp('timestamp_data', size: 6);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('timestamp_data')->getInternalType(), 'timestamp');
        Assert::same($schema->column('timestamp_data')->getSize(), 6);
    }

    #[Test]
    public function testTimestampTzWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->timestamptz('timestamp_data', size: 6);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('timestamp_data')->getInternalType(), 'timestamp');
        Assert::same($schema->column('timestamp_data')->getSize(), 6);
    }
}
