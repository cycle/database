<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\ConsistencyTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlite')]
class ConsistencyTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
