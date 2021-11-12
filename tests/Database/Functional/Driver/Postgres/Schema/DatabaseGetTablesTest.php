<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * @group driver
 * @group driver-postgres
 */
class DatabaseGetTablesTest extends BaseTest
{
    public const DRIVER = 'postgres';

    public function testPrefixedTableWithSchemaShouldBeParsedCorrect(): void
    {
        $db = $this->db(prefix: 'prefix_');
        $this->assertFalse($db->hasTable('test'));
        $this->assertFalse($db->hasTable('sample_test'));

        $schema = $db->test->getSchema();
        $schema->primary('id');
        $schema->save();

        $schema = $db->sample_test->getSchema();
        $schema->primary('id');
        $schema->save();

        $this->assertTrue($db->hasTable('test'));
        $this->assertTrue($db->hasTable('sample_test'));

        $tables = array_map(fn($table) => $table->getName(), $db->getTables());

        $this->assertTrue(in_array('public.test', $tables));
        $this->assertTrue(in_array('public.sample_test', $tables));
    }
}
