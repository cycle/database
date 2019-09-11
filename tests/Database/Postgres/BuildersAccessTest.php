<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests\Postgres;

use Spiral\Database\Driver\Postgres\Query\PostgresInsertQuery;
use Spiral\Database\Driver\Postgres\Schema\PostgresTable;

class BuildersAccessTest extends \Spiral\Database\Tests\BuildersAccessTest
{
    const DRIVER = 'postgres';

    public function testTableSchemaAccess()
    {
        parent::testTableSchemaAccess();
        $this->assertInstanceOf(
            PostgresTable::class,
            $this->db()->table('sample')->getSchema()
        );
    }

    public function testInsertQueryAccess()
    {
        parent::testInsertQueryAccess();
        $this->assertInstanceOf(PostgresInsertQuery::class, $this->db()->insert());
    }
}
