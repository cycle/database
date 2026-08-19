<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\BulkIntrospectionTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class BulkIntrospectionTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    protected function isBatchedProvider(): bool
    {
        return true;
    }
}
