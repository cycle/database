<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database\SQLite;

use Spiral\Database\Drivers\SQLite\Schemas\SQLiteTable;

class BuildersAccessTest extends \Spiral\Tests\Database\BuildersAccessTest
{
    use DriverTrait;

    public function testTableSchemaAccess()
    {
        parent::testTableSchemaAccess();
        $this->assertInstanceOf(
            SQLiteTable::class,
            $this->database()->table('sample')->getSchema()
        );
    }
}