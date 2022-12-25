<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
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
}
