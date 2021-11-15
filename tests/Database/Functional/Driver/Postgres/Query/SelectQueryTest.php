<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\SelectQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class SelectQueryTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
