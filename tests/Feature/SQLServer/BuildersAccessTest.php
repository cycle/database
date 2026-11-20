<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLServer;

use Cycle\Database\Driver\SQLServer\Schema\SQLServerTable;
use Cycle\Database\Tests\Feature\Common\BuildersAccessTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::SQLServer)]
final class BuildersAccessTest extends CommonClass
{
    #[Test]
    public function testTableSchemaAccess(): void
    {
        parent::testTableSchemaAccess();
        Assert::instanceOf($this->db()->table('sample')->getSchema(), SQLServerTable::class);
    }
}
