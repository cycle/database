<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests\MySQL;

class SelectQueryTest extends \Spiral\Database\Tests\SelectQueryTest
{
    const DRIVER = 'mysql';

    public function testOffsetNoLimit()
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        $this->assertSameQuery(
            "SELECT * FROM {users} LIMIT 18446744073709551615 OFFSET 20",
            $select
        );
    }
}
