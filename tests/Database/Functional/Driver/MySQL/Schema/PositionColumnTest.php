<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

use Cycle\Database\Tests\Functional\Driver\Common\Schema\PositionColumnTest as BaseTest;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class PositionColumnTest extends BaseTest
{
    public const DRIVER = 'mysql';
}
