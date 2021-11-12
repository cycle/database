<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL;

// phpcs:ignore
use Cycle\Database\Driver\MySQL\Schema\MySQLTable;
use Cycle\Database\Tests\Functional\Driver\Common\BuildersAccessTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class BuildersAccessTest extends CommonClass
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
