<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Exception\HandlerException;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\DatetimeColumnTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class DatetimeColumnTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testTimestampDatetimeZero(): void
    {
        $this->expectExceptionMessage(
            "SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid default value for 'target'",
        );

        \Testo\Expect::exception(HandlerException::class);
        parent::testTimestampDatetimeZero();
    }

    #[\Testo\Test]
    public function testTimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->time('time_data', size: 3);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('time_data')->getInternalType(), 'time');
        \Testo\Assert::same($schema->column('time_data')->getSize(), 3);
    }

    #[\Testo\Test]
    public function testTimestampWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->timestamp('timestamp_data', size: 3);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('timestamp_data')->getInternalType(), 'timestamp');
        \Testo\Assert::same($schema->column('timestamp_data')->getSize(), 3);
    }

    #[\Testo\Test]
    public function testDatetimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->datetime('datetime_data', size: 3);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('datetime_data')->getInternalType(), 'datetime');
        \Testo\Assert::same($schema->column('datetime_data')->getSize(), 3);
    }
}
