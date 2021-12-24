<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\PostgresCustom\Driver;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\StatementTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres_custom_pdo_options
 */
class StatementTest extends CommonClass
{
    public const DRIVER = 'postgres_custom_pdo_options';
}
