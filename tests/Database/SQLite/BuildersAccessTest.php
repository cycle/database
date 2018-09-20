<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests\SQLite;

use Spiral\Database\Driver\SQLite\Schema\SQLiteTable;

class BuildersAccessTest extends \Spiral\Database\Tests\BuildersAccessTest
{
    const DRIVER = 'sqlite';

    public function testTableSchemaAccess()
    {
        parent::testTableSchemaAccess();
        $this->assertInstanceOf(
            SQLiteTable::class,
            $this->db()->table('sample')->getSchema()
        );
    }
}