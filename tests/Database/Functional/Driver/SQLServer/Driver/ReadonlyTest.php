<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Driver;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\ReadonlyTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ReadonlyTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
