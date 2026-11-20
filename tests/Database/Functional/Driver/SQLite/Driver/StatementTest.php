<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Driver;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\StatementTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlite')]
class StatementTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
