<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\SQLite;

class SelectQueryTest extends \Spiral\Database\Tests\SelectQueryTest
{
    public const DRIVER = 'sqlite';

    public function testOffsetNoLimit(): void
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        $this->assertSameQuery(
            'SELECT * FROM {users} LIMIT -1 OFFSET 20',
            $select
        );
    }

    public function testSelectForUpdate(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', 'Antony')
            ->forUpdate();

        $this->assertSameQuery(
            'SELECT * FROM {users} WHERE {name} = ?',
            $select
        );
    }
}
