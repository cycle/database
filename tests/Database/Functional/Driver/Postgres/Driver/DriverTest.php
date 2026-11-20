<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Driver;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\DriverTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class DriverTest extends CommonClass
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testSchemaPathAfterReconnect(): void
    {
        $db = $this->db(driverConfig: ['schema' => ['custom']]);

        $ref = new \ReflectionProperty($db->getDriver(), 'searchPath');
        $ref->setAccessible(true);

        \Testo\Assert::same($ref->getValue($db->getDriver()), ['custom']);

        $db->getDriver()->disconnect();
        $db->getDriver()->connect();

        \Testo\Assert::same($ref->getValue($db->getDriver()), ['custom']);
    }
}
