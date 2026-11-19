<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\JsonPathEscapingTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class JsonPathEscapingTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
