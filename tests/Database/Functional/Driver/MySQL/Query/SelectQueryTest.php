<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\SelectQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class SelectQueryTest extends CommonClass
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
