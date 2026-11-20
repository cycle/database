<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\BuildersAccessTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlite')]
class BuildersAccessTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
