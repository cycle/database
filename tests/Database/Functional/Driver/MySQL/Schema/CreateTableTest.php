<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\CreateTableTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class CreateTableTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
