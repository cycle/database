<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\Driver\MySQL;

use Spiral\Database\Exception\HandlerException;

/**
 * @group driver
 * @group driver-mysql
 */
class DatetimeColumnTest extends \Spiral\Database\Tests\DatetimeColumnTest
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
