<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Query;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Driver\Handler;
use Cycle\Database\Exception\CompilerException;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * Cross-driver behaviour expectations for upsert. Driver-specific SQL assertions live in
 * per-driver subclasses.
 */
abstract class UpsertQueryTest extends BaseTest
{
    public function testOnConflictTurnsInsertIntoUpsertType(): void
    {
        $q = $this->database->insert('table')->values(['email' => 'a@b.c']);
        $this->assertSame(CompilerInterface::INSERT_QUERY, $q->getType());

        $q->onConflict('email');
        $this->assertSame(CompilerInterface::UPSERT_QUERY, $q->getType());
    }

    public function testShorthandIsEquivalentToTargetDoUpdate(): void
    {
        $a = $this->database->insert('t')->values(['email' => 'x', 'name' => 'y'])
            ->onConflict('email');

        $b = $this->database->insert('t')->values(['email' => 'x', 'name' => 'y'])
            ->onConflict(OnConflict::target('email')->doUpdate());

        $this->assertSame($a->sqlStatement(), $b->sqlStatement());
    }

    public function testShorthandAcceptsArrayOfColumns(): void
    {
        $q = $this->database->insert('t')
            ->values(['tenant_id' => 1, 'email' => 'x', 'name' => 'y'])
            ->onConflict(['tenant_id', 'email']);

        $this->assertSame(CompilerInterface::UPSERT_QUERY, $q->getType());
        $this->assertInstanceOf(InsertQuery::class, $q);
    }

    public function testOnConflictReturnsInsertQuery(): void
    {
        $q = $this->database->insert('t')->values(['email' => 'x']);
        $this->assertSame($q, $q->onConflict('email'));
    }

    public function testEmptyValuesRejected(): void
    {
        $q = $this->database->insert('t')
            ->onConflict(OnConflict::target('email')->doUpdate());

        $this->expectException(CompilerException::class);
        (string) $q;
    }

    // --- Runtime (execution against a live database) ---

    public function testRuntimeDoUpdateUpdatesExistingRow(): void
    {
        $this->makeUpsertUsersTable();
        $this->database->insert('upsert_users')->values(['email' => 'a@b.c', 'name' => 'Old'])->run();

        $this->database->insert('upsert_users')
            ->values(['email' => 'a@b.c', 'name' => 'New'])
            ->onConflict('email')
            ->run();

        $rows = $this->database->select()->from('upsert_users')->fetchAll();
        $this->assertCount(1, $rows, 'Conflicting row must be updated, not duplicated.');
        $this->assertSame('New', $rows[0]['name']);
    }

    public function testRuntimeDoUpdateSelectiveColumns(): void
    {
        $this->makeUpsertUsersTable();
        $this->database->insert('upsert_users')
            ->values(['email' => 'a@b.c', 'name' => 'Old', 'tag' => 'keep'])->run();

        $this->database->insert('upsert_users')
            ->values(['email' => 'a@b.c', 'name' => 'New', 'tag' => 'drop'])
            ->onConflict(OnConflict::target('email')->doUpdate(['name']))
            ->run();

        $rows = $this->database->select()->from('upsert_users')->fetchAll();
        $this->assertCount(1, $rows);
        $this->assertSame('New', $rows[0]['name']);
        $this->assertSame('keep', $rows[0]['tag'], 'Columns outside the update list must be preserved.');
    }

    public function testRuntimeDoNothingPreservesExistingRow(): void
    {
        $this->makeUpsertUsersTable();
        $this->database->insert('upsert_users')->values(['email' => 'a@b.c', 'name' => 'Old'])->run();

        $this->database->insert('upsert_users')
            ->values(['email' => 'a@b.c', 'name' => 'New'])
            ->onConflict(OnConflict::target('email')->doNothing())
            ->run();

        $rows = $this->database->select()->from('upsert_users')->fetchAll();
        $this->assertCount(1, $rows);
        $this->assertSame('Old', $rows[0]['name'], 'DO NOTHING must keep the original row.');
    }

    public function testRuntimeInsertsWhenNoConflict(): void
    {
        $this->makeUpsertUsersTable();
        $this->database->insert('upsert_users')->values(['email' => 'a@b.c', 'name' => 'Old'])->run();

        $this->database->insert('upsert_users')
            ->values(['email' => 'x@y.z', 'name' => 'New'])
            ->onConflict('email')
            ->run();

        $this->assertCount(2, $this->database->select()->from('upsert_users')->fetchAll());
    }

    private function makeUpsertUsersTable(): void
    {
        $schema = $this->schema('upsert_users');
        $schema->primary('id');
        $schema->string('email');
        $schema->string('name');
        $schema->string('tag')->nullable(true);
        $schema->index(['email'])->unique(true);
        $schema->save(Handler::DO_ALL);
    }
}
