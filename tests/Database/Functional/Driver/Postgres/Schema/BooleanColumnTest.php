<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\BooleanColumnTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
final class BooleanColumnTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
