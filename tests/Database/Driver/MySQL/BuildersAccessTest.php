<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\Driver\MySQL;

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
