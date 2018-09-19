<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests\MySQL;

use Spiral\Database\Driver\MySQL\Schema\MySQLTable;

class BuildersAccessTest extends \Spiral\Database\Tests\BuildersAccessTest
{
    const DRIVER = 'mysql';

    public function testTableSchemaAccess()
    {
        parent::testTableSchemaAccess();
        $this->assertInstanceOf(
            MySQLTable::class,
            $this->db()->table('sample')->getSchema()
        );
    }
}