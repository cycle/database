<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\NestedQueriesTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class NestedQueriesTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
