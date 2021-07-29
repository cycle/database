<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\SQLite;

/**
 * @group driver
 * @group driver-sqlite
 */
class InsertQueryTest extends \Cycle\Database\Tests\InsertQueryTest
{
    public const DRIVER = 'sqlite';

    public function testSimpleInsertMultipleRows(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->values('John', 200);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) SELECT ? AS {name}, ? AS {balance}
UNION ALL SELECT ?, ?',
            $insert
        );
    }

    public function testSimpleInsertMultipleRows2(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->values('John', 200)
            ->values('Pitt', 200);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) SELECT ? AS {name}, ? AS {balance}'
            . ' UNION ALL SELECT ?, ?'
            . ' UNION ALL SELECT ?, ?',
            $insert
        );
    }
}
