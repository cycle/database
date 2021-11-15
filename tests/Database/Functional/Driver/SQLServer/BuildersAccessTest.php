<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer;

// phpcs:ignore
use Cycle\Database\Driver\SQLServer\Schema\SQLServerTable;
use Cycle\Database\Tests\Functional\Driver\Common\BuildersAccessTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class BuildersAccessTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    public function testTableSchemaAccess(): void
    {
        parent::testTableSchemaAccess();
        $this->assertInstanceOf(
            SQLServerTable::class,
            $this->db()->table('sample')->getSchema()
        );
    }
}
