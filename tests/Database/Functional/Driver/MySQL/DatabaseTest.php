<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\DatabaseTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class DatabaseTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
