<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests\SQLite;

class SelectQueryTest extends \Spiral\Database\Tests\SelectQueryTest
{
    const DRIVER = 'sqlite';

    public function testOffsetNoLimit()
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        $this->assertSameQuery(
            "SELECT * FROM {users} LIMIT -1 OFFSET 20",
            $select
        );
    }
}