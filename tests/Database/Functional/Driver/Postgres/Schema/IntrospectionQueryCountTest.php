<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\IntrospectionQueryCountTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class IntrospectionQueryCountTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
