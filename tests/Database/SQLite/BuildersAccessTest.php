<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\SQLite;

use Spiral\Database\Driver\SQLite\Schema\SQLiteTable;

class BuildersAccessTest extends \Spiral\Database\Tests\BuildersAccessTest
{
    public const DRIVER = 'sqlite';

    public function testTableSchemaAccess(): void
    {
        parent::testTableSchemaAccess();
        $this->assertInstanceOf(
            SQLiteTable::class,
            $this->db()->table('sample')->getSchema()
        );
    }
}
