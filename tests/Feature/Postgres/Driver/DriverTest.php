<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Driver;

use Cycle\Database\Tests\Feature\Common\Driver\DriverTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::Postgres)]
final class DriverTest extends CommonClass
{
    #[Test]
    public function testSchemaPathAfterReconnect(): void
    {
        $db = $this->db(driverConfig: ['schema' => ['custom']]);

        $ref = new \ReflectionProperty($db->getDriver(), 'searchPath');
        $ref->setAccessible(true);

        Assert::same($ref->getValue($db->getDriver()), ['custom']);

        $db->getDriver()->disconnect();
        $db->getDriver()->connect();

        Assert::same($ref->getValue($db->getDriver()), ['custom']);
    }
}
