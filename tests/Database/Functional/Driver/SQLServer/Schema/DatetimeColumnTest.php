<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\DatetimeColumnTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
class DatetimeColumnTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    #[\Testo\Test]
    public function testDatetimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->datetime('datetime_data', size: 6);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('datetime_data')->getInternalType(), 'datetime2');
        \Testo\Assert::same($schema->column('datetime_data')->getSize(), 6);
    }
}
