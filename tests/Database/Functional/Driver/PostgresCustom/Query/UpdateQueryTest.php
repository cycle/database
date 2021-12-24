<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\PostgresCustom\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpdateQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres_custom_pdo_options
 */
class UpdateQueryTest extends CommonClass
{
    public const DRIVER = 'postgres_custom_pdo_options';
}
