<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL;

// phpcs:ignore
use Cycle\Database\Driver\MySQL\Schema\MySQLTable;
use Cycle\Database\Tests\Functional\Driver\Common\BuildersAccessTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class BuildersAccessTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testTableSchemaAccess(): void
    {
        parent::testTableSchemaAccess();
        \Testo\Assert::instanceOf($this->db()->table('sample')->getSchema(), MySQLTable::class);
    }
}
