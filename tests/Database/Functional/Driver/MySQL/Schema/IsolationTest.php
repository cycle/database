<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\IsolationTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class IsolationTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
