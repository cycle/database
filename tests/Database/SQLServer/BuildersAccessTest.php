<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests\SQLServer;

use Spiral\Database\Driver\SQLServer\Schema\SQLServerTable;

class BuildersAccessTest extends \Spiral\Database\Tests\BuildersAccessTest
{
    const DRIVER = 'sqlserver';

    public function testTableSchemaAccess()
    {
        parent::testTableSchemaAccess();
        $this->assertInstanceOf(
            SQLServerTable::class,
            $this->db()->table('sample')->getSchema()
        );
    }
}