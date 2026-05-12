<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Driver;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\CursorTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class CursorTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
