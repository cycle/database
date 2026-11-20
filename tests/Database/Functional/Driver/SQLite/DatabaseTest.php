<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\DatabaseTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlite')]
class DatabaseTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
