<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class DatabaseGetTablesTest extends BaseTest
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testPrefixedTableWithSchemaShouldBeParsedCorrect(): void
    {
        $db = $this->db(prefix: 'prefix_');
        \Testo\Assert::false($db->hasTable('test'));
        \Testo\Assert::false($db->hasTable('sample_test'));

        $schema = $db->test->getSchema();
        $schema->primary('id');
        $schema->save();

        $schema = $db->sample_test->getSchema();
        $schema->primary('id');
        $schema->save();

        \Testo\Assert::true($db->hasTable('test'));
        \Testo\Assert::true($db->hasTable('sample_test'));

        $tables = \array_map(static fn($table) => $table->getName(), $db->getTables());

        \Testo\Assert::true(\in_array('public.test', $tables));
        \Testo\Assert::true(\in_array('public.sample_test', $tables));
    }
}
