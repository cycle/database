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
class InsertQueryTest extends \Cycle\Database\Tests\InsertQueryTest
{
    public const DRIVER = 'mysql';

    public function testCompileQueryDefaults(): void
    {
        $insert = $this->db()->insert('table')->values([]);

        $this->assertSameQuery(
            'INSERT INTO {table} () VALUES ()',
            (string)$insert
        );
    }

    public function testSimpleInsertEmptyDataset(): void
    {
        $insert = $this->database->insert()->into('table')->values([]);

        $this->assertSameQuery(
            'INSERT INTO {table} () VALUES ()',
            $insert
        );
    }
}
