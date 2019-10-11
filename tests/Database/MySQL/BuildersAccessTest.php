<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
declare(strict_types=1);

namespace Spiral\Database\Tests\MySQL;

use Spiral\Database\Driver\MySQL\Schema\MySQLTable;

class BuildersAccessTest extends \Spiral\Database\Tests\BuildersAccessTest
{
    public const DRIVER = 'mysql';

    public function testTableSchemaAccess(): void
    {
        parent::testTableSchemaAccess();
        $this->assertInstanceOf(
            MySQLTable::class,
            $this->db()->table('sample')->getSchema()
        );
    }
}
