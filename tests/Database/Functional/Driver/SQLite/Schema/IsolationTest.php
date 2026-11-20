<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\IsolationTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlite')]
class IsolationTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
