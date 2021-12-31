<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\PostgresCustom\Connection;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Connection\CustomOptionsTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres_custom_pdo_options
 */
class CustomOptionsTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
