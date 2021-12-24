<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\PostgresCustom;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\DatabaseTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres_custom_pdo_options
 */
class DatabaseTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
