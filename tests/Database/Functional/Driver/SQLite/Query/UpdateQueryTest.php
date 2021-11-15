<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpdateQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class UpdateQueryTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
