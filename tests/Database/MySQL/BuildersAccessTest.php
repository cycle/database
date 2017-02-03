<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database\MySQL;

use Spiral\Database\Drivers\MySQL\Schemas\MySQLTable;

class BuildersAccessTest extends \Spiral\Tests\Database\BuildersAccessTest
{
    use DriverTrait;

    public function testTableSchemaAccess()
    {
        parent::testTableSchemaAccess();
        $this->assertInstanceOf(
            MySQLTable::class,
            $this->database()->table('sample')->getSchema()
        );
    }
}