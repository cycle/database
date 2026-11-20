<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\DatabaseTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class DatabaseTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
