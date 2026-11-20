<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\IndexesTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class IndexesTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
