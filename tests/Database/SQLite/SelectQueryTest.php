<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database\SQLite;

class SelectQueryTest extends \Spiral\Tests\Database\SelectQueryTest
{
    use DriverTrait;

    public function testOffsetNoLimit()
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        $this->assertSameQuery(
            "SELECT * FROM {users} LIMIT -1 OFFSET 20",
            $select
        );
    }
}