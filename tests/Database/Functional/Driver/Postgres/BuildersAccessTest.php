<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\BuildersAccessTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class BuildersAccessTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
