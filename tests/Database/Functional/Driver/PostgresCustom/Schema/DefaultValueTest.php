<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\PostgresCustom\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\DefaultValueTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres_custom_pdo_options
 */
class DefaultValueTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
