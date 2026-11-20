<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Query;

use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Test;

final class MysqlTransactionsTest extends DatabaseTestCase
{
    /**
     * The atomic DDL of MySQL 8.0 is non transactional.
     */
    #[Test]
    public function testRollbackDDLChanges(): void
    {
        // Creating a new table with primary field
        $table = $this->database->table('table');
        $schema = $table->getSchema();
        $schema->primary('id');
        $schema->save();

        $this->database->begin();

        // Add a new field on a first transaction level
        $schema->integer('value');
        $schema->save();

        Assert::true($table->hasColumn('value'));

        // Nested savepoint
        $this->database->begin();

        // Add another one field on a second transaction level
        $schema->integer('new_value');
        $schema->save();

        Assert::true($table->hasColumn('new_value'));

        // Revert changes on the second level
        $this->database->rollback();

        // Commit changes on the first level
        $this->database->commit();

        Assert::true($table->hasColumn('value'));
        // This column will exist after rollback
        Assert::true($table->hasColumn('new_value'));
    }
}
