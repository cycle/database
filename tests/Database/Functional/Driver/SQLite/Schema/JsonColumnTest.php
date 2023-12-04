<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\JsonColumnTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class JsonColumnTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    public function testColumnSizeIsIgnored(): void
    {
        $this->markTestSkipped('SQLite stores JSON as `text` and its length can be changed.');
    }
}
