<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\MySQL;

use Cycle\Database\Driver\MySQL\Schema\MySQLTable;

/**
 * @group driver
 * @group driver-mysql
 */
class BuildersAccessTest extends \Cycle\Database\Tests\BuildersAccessTest
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
