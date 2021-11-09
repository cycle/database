<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\Postgres;

/**
 * @group driver
 * @group driver-postgres
 */
class DatabaseGetTablesTest extends \Cycle\Database\Tests\BaseTest
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

        $tables = $db->getTables();

        $this->assertSame('public.test', $tables[0]->getName());
        $this->assertSame('public.sample_test', $tables[1]->getName());
    }
}
