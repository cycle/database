<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\SQLServer;

use Cycle\Database\Driver\SQLServer\Schema\SQLServerTable;

/**
 * @group driver
 * @group driver-sqlserver
 */
class BuildersAccessTest extends \Cycle\Database\Tests\BuildersAccessTest
{
    public const DRIVER = 'sqlserver';

    public function testTableSchemaAccess(): void
    {
        parent::testTableSchemaAccess();
        $this->assertInstanceOf(
            SQLServerTable::class,
            $this->db()->table('sample')->getSchema()
        );
    }
}
