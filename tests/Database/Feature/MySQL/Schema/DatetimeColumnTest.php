<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Exception\HandlerException;
use Cycle\Database\Tests\Feature\Common\Schema\DatetimeColumnTest as CommonClass;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[OnlyDriver(Driver::MySQL)]
final class DatetimeColumnTest extends CommonClass
{
    #[Test]
    public function testTimestampDatetimeZero(): void
    {
        Expect::exception(HandlerException::class)->withMessageContaining(
            "SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid default value for 'target'",
        );
        parent::testTimestampDatetimeZero();
    }

    #[Test]
    public function testTimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->time('time_data', size: 3);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('time_data')->getInternalType(), 'time');
        Assert::same($schema->column('time_data')->getSize(), 3);
    }

    #[Test]
    public function testTimestampWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->timestamp('timestamp_data', size: 3);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('timestamp_data')->getInternalType(), 'timestamp');
        Assert::same($schema->column('timestamp_data')->getSize(), 3);
    }

    #[Test]
    public function testDatetimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->datetime('datetime_data', size: 3);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('datetime_data')->getInternalType(), 'datetime');
        Assert::same($schema->column('datetime_data')->getSize(), 3);
    }
}
