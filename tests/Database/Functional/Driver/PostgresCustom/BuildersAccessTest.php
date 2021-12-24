<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\PostgresCustom;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\BuildersAccessTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres_custom_pdo_options
 */
class BuildersAccessTest extends CommonClass
{
    public const DRIVER = 'postgres_custom_pdo_options';
}
