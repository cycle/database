<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\BulkIntrospectionTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class BulkIntrospectionTest extends CommonClass
{
    public const DRIVER = 'postgres';

    protected function isBatchedProvider(): bool
    {
        return true;
    }
}
