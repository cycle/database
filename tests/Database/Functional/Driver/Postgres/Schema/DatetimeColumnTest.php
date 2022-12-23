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

    public function testTimestampWithTimezone(): void
    {
        $schema = $this->schema('timestamp_with_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timestamptz('timestamptz');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $ref = new \ReflectionProperty($column, 'withTimezone');
        $ref->setAccessible(true);

        $this->assertTrue($ref->getValue($column));
        $this->assertSame('timestamptz', $column->getAbstractType());
    }

    public function testTimeWithTimezone(): void
    {
        $schema = $this->schema('time_with_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timetz('timetz');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $ref = new \ReflectionProperty($column, 'withTimezone');
        $ref->setAccessible(true);

        $this->assertTrue($ref->getValue($column));
        $this->assertSame('timetz', $column->getAbstractType());
    }

    public function testTimestampWithoutTimezone(): void
    {
        $schema = $this->schema('timestamp_without_tz');

        /** @var PostgresColumn $column */
        $column = $schema->timestamp('timestamp');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $ref = new \ReflectionProperty($column, 'withTimezone');
        $ref->setAccessible(true);

        $this->assertFalse($ref->getValue($column));
        $this->assertSame('timestamp', $column->getAbstractType());
    }

    public function testTimeWithoutTimezone(): void
    {
        $schema = $this->schema('time_without_tz');

        /** @var PostgresColumn $column */
        $column = $schema->time('time');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $ref = new \ReflectionProperty($column, 'withTimezone');
        $ref->setAccessible(true);

        $this->assertFalse($ref->getValue($column));
        $this->assertSame('time', $column->getAbstractType());
    }
}
