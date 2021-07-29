<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\MySQL;

use Cycle\Database\Exception\HandlerException;

/**
 * @group driver
 * @group driver-mysql
 */
class DatetimeColumnTest extends \Cycle\Database\Tests\DatetimeColumnTest
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
