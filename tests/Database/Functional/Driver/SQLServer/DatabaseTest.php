<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\DatabaseTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class DatabaseTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
