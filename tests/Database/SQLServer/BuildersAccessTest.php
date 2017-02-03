<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database\SQLServer;

use Spiral\Database\Drivers\SQLServer\Schemas\SQLServerTable;

class BuildersAccessTest extends \Spiral\Tests\Database\BuildersAccessTest
{
    use DriverTrait;

    public function testTableSchemaAccess()
    {
        parent::testTableSchemaAccess();
        $this->assertInstanceOf(
            SQLServerTable::class,
            $this->database()->table('sample')->getSchema()
        );
    }
}