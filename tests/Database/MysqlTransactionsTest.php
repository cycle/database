<?php

declare(strict_types=1);

namespace Cycle\Database\Tests;

/**
 * @group driver
 * @group driver-mysql
 */
class MysqlTransactionsTest extends BaseTest
{
    public const DRIVER = 'mysql';

    /**
     * The atomic DDL of MySQL 8.0 is non transactional.
     */
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

        $this->assertTrue($table->hasColumn('value'));

        // Nested savepoint
        $this->database->begin();

        // Add another one field on a second transaction level
        $schema->integer('new_value');
        $schema->save();

        $this->assertTrue($table->hasColumn('new_value'));

        // Revert changes on the second level
        $this->database->rollback();

        // Commit changes on the first level
        $this->database->commit();

        $this->assertTrue($table->hasColumn('value'));
        // This column will exist after rollback
        $this->assertTrue($table->hasColumn('new_value'));
    }
}
