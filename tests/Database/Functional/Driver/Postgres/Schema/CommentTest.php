<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\CommentTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class CommentTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
