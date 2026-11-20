<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\JsonColumnTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlite')]
class JsonColumnTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    #[\Testo\Test]
    #[\Testo\Skip('SQLite stores JSON as `text` and its length can be changed.')]
    public function testColumnSizeIsIgnored(): void
    {
    }
}
