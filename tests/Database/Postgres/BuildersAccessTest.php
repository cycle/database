<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
declare(strict_types=1);

namespace Spiral\Database\Tests\Postgres;

use Spiral\Database\Driver\Postgres\Query\PostgresInsertQuery;
use Spiral\Database\Driver\Postgres\Schema\PostgresTable;

class BuildersAccessTest extends \Spiral\Database\Tests\BuildersAccessTest
{
    public const DRIVER = 'postgres';

    public function testTableSchemaAccess(): void
    {
        parent::testTableSchemaAccess();
        $this->assertInstanceOf(
            PostgresTable::class,
            $this->db()->table('sample')->getSchema()
        );
    }

    public function testInsertQueryAccess(): void
    {
        parent::testInsertQueryAccess();
        $this->assertInstanceOf(PostgresInsertQuery::class, $this->db()->insert());
    }
}
