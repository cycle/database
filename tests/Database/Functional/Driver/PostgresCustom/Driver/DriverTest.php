<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\PostgresCustom\Driver;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\DriverTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres_custom_pdo_options
 */
class DriverTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
