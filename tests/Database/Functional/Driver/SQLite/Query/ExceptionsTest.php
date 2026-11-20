<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\ExceptionsTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlite')]
class ExceptionsTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
