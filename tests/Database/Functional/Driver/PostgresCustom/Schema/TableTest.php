<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\PostgresCustom\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\TableTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres_custom_pdo_options
 */
class TableTest extends CommonClass
{
    public const DRIVER = 'postgres_custom_pdo_options';
}
