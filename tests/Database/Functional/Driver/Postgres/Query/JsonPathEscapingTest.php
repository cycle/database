<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\JsonPathEscapingTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class JsonPathEscapingTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
