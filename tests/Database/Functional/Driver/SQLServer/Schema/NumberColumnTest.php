<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\NumberColumnTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
final class NumberColumnTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
