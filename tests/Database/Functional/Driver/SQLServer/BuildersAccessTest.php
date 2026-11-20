<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer;

// phpcs:ignore
use Cycle\Database\Driver\SQLServer\Schema\SQLServerTable;
use Cycle\Database\Tests\Functional\Driver\Common\BuildersAccessTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
class BuildersAccessTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    #[\Testo\Test]
    public function testTableSchemaAccess(): void
    {
        parent::testTableSchemaAccess();
        \Testo\Assert::instanceOf($this->db()->table('sample')->getSchema(), SQLServerTable::class);
    }
}
