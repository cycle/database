<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Connection;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Connection\CustomOptionsTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CustomOptionsTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
