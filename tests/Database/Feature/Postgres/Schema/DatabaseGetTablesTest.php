<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Test;

final class DatabaseGetTablesTest extends DatabaseTestCase
{
    #[Test]
    public function testPrefixedTableWithSchemaShouldBeParsedCorrect(): void
    {
        $db = $this->db(prefix: 'prefix_');
        Assert::false($db->hasTable('test'));
        Assert::false($db->hasTable('sample_test'));

        $schema = $db->test->getSchema();
        $schema->primary('id');
        $schema->save();

        $schema = $db->sample_test->getSchema();
        $schema->primary('id');
        $schema->save();

        Assert::true($db->hasTable('test'));
        Assert::true($db->hasTable('sample_test'));

        $tables = \array_map(static fn($table) => $table->getName(), $db->getTables());

        Assert::true(\in_array('public.test', $tables));
        Assert::true(\in_array('public.sample_test', $tables));
    }
}
