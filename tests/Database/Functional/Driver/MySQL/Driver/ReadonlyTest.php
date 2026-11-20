<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Driver;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\ReadonlyTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class ReadonlyTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
