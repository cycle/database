<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database\Postgres;

use Spiral\Database\Drivers\Postgres\PostgresInsertQuery;
use Spiral\Database\Drivers\Postgres\Schemas\PostgresTable;

class BuildersAccessTest extends \Spiral\Tests\Database\BuildersAccessTest
{
    use DriverTrait;

    public function testTableSchemaAccess()
    {
        parent::testTableSchemaAccess();
        $this->assertInstanceOf(
            PostgresTable::class,
            $this->database()->table('sample')->getSchema()
        );
    }

    public function testInsertQueryAccess()
    {
        parent::testInsertQueryAccess();
        $this->assertInstanceOf(PostgresInsertQuery::class, $this->database()->insert());
    }
}