<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\JsonColumnTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class JsonColumnTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    public function testColumnSizeIsIgnored(): void
    {
        $this->markTestSkipped('SQLServer stores JSON as `varchar` and its length can be changed.');
    }
}
