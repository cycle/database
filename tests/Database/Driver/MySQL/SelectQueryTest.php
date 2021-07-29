<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\MySQL;

/**
 * @group driver
 * @group driver-mysql
 */
class SelectQueryTest extends \Cycle\Database\Tests\SelectQueryTest
{
    public const DRIVER = 'mysql';

    public function testOffsetNoLimit(): void
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        $this->assertSameQuery(
            'SELECT * FROM {users} LIMIT 18446744073709551615 OFFSET ?',
            $select
        );

        $this->assertSameParameters([20], $select);
    }
}
