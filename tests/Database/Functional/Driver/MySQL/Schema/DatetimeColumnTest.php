<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Exception\HandlerException;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\DatetimeColumnTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class DatetimeColumnTest extends CommonClass
{
    public const DRIVER = 'mysql';

    public function testTimestampDatetimeZero(): void
    {
        $this->expectExceptionMessage(
            "SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid default value for 'target'"
        );

        $this->expectException(HandlerException::class);
        parent::testTimestampDatetimeZero();
    }

    public function testTimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->time('time_data', size: 3);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('time', $schema->column('time_data')->getInternalType());
        $this->assertSame(3, $schema->column('time_data')->getSize());
    }

    public function testTimestampWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->timestamp('timestamp_data', size: 3);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('timestamp', $schema->column('timestamp_data')->getInternalType());
        $this->assertSame(3, $schema->column('timestamp_data')->getSize());
    }

    public function testDatetimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->datetime('datetime_data', size: 3);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('datetime', $schema->column('datetime_data')->getInternalType());
        $this->assertSame(3, $schema->column('datetime_data')->getSize());
    }
}
