<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL;

use Cycle\Database\Driver\MySQL\Schema\MySQLTable;
use Cycle\Database\Tests\Feature\Common\BuildersAccessTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::MySQL)]
final class BuildersAccessTest extends CommonClass
{
    #[Test]
    public function testTableSchemaAccess(): void
    {
        parent::testTableSchemaAccess();
        Assert::instanceOf($this->db()->table('sample')->getSchema(), MySQLTable::class);
    }
}
