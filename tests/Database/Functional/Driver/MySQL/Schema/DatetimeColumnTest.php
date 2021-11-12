<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
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
}
