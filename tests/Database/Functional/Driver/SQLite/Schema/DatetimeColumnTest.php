<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\DatetimeColumnTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class DatetimeColumnTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
