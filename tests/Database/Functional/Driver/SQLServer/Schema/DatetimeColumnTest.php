<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\DatetimeColumnTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class DatetimeColumnTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    public function testDatetimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->datetime('datetime_data', size: 6);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('datetime2', $schema->column('datetime_data')->getInternalType());
        $this->assertSame(6, $schema->column('datetime_data')->getSize());
    }
}
