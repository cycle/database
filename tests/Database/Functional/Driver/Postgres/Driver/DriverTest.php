<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Driver;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\DriverTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class DriverTest extends CommonClass
{
    public const DRIVER = 'postgres';

    public function testSchemaPathAfterReconnect(): void
    {
        $db = $this->db(driverConfig: ['schema' => ['custom']]);

        $ref = new \ReflectionProperty($db->getDriver(), 'searchPath');
        $ref->setAccessible(true);

        $this->assertSame(['custom'], $ref->getValue($db->getDriver()));

        $db->getDriver()->disconnect();
        $db->getDriver()->connect();

        $this->assertSame(['custom'], $ref->getValue($db->getDriver()));
    }
}
