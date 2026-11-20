<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\JsonColumnTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
class JsonColumnTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    #[\Testo\Test]
    #[\Testo\Skip('SQLServer stores JSON as `varchar` and its length can be changed.')]
    public function testColumnSizeIsIgnored(): void
    {
    }
}
