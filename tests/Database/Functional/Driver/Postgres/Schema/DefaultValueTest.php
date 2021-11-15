<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\DefaultValueTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class DefaultValueTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
