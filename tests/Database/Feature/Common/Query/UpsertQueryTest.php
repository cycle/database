<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Query;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Driver\Handler;
use Cycle\Database\Exception\CompilerException;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\OnConflict;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

/**
 * Cross-driver behaviour expectations for upsert. Driver-specific SQL assertions live in
 * per-driver subclasses.
 */
abstract class UpsertQueryTest extends DatabaseTestCase
{
    #[Test]
    public function testOnConflictTurnsInsertIntoUpsertType(): void
    {
        $q = $this->database->insert('table')->values(['email' => 'a@b.c']);
        Assert::same($q->getType(), CompilerInterface::INSERT_QUERY);

        $q->onConflict('email');
        Assert::same($q->getType(), CompilerInterface::UPSERT_QUERY);
    }

    #[Test]
    public function testShorthandIsEquivalentToTargetDoUpdate(): void
    {
        $a = $this->database->insert('t')->values(['email' => 'x', 'name' => 'y'])
            ->onConflict('email');

        $b = $this->database->insert('t')->values(['email' => 'x', 'name' => 'y'])
            ->onConflict(OnConflict::target('email')->doUpdate());

        Assert::same($b->sqlStatement(), $a->sqlStatement());
    }

    #[Test]
    public function testShorthandAcceptsArrayOfColumns(): void
    {
        $q = $this->database->insert('t')
            ->values(['tenant_id' => 1, 'email' => 'x', 'name' => 'y'])
            ->onConflict(['tenant_id', 'email']);

        Assert::same($q->getType(), CompilerInterface::UPSERT_QUERY);
        Assert::instanceOf($q, InsertQuery::class);
    }

    #[Test]
    public function testOnConflictReturnsInsertQuery(): void
    {
        $q = $this->database->insert('t')->values(['email' => 'x']);
        Assert::same($q->onConflict('email'), $q);
    }

    #[Test]
    public function testEmptyValuesRejected(): void
    {
        $q = $this->database->insert('t')
            ->onConflict(OnConflict::target('email')->doUpdate());

        Expect::exception(CompilerException::class);
        (string) $q;
    }

    // --- Runtime (execution against a live database) ---

    #[Test]
    public function testRuntimeDoUpdateUpdatesExistingRow(): void
    {
        $this->makeUpsertUsersTable();
        $this->database->insert('upsert_users')->values(['email' => 'a@b.c', 'name' => 'Old'])->run();

        $this->database->insert('upsert_users')
            ->values(['email' => 'a@b.c', 'name' => 'New'])
            ->onConflict('email')
            ->run();

        $rows = $this->database->select()->from('upsert_users')->fetchAll();
        Assert::count($rows, 1, 'Conflicting row must be updated, not duplicated.');
        Assert::same($rows[0]['name'], 'New');
    }

    #[Test]
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
        Assert::count($rows, 1);
        Assert::same($rows[0]['name'], 'New');
        Assert::same($rows[0]['tag'], 'keep', 'Columns outside the update list must be preserved.');
    }

    #[Test]
    public function testRuntimeDoNothingPreservesExistingRow(): void
    {
        $this->makeUpsertUsersTable();
        $this->database->insert('upsert_users')->values(['email' => 'a@b.c', 'name' => 'Old'])->run();

        $this->database->insert('upsert_users')
            ->values(['email' => 'a@b.c', 'name' => 'New'])
            ->onConflict(OnConflict::target('email')->doNothing())
            ->run();

        $rows = $this->database->select()->from('upsert_users')->fetchAll();
        Assert::count($rows, 1);
        Assert::same($rows[0]['name'], 'Old', 'DO NOTHING must keep the original row.');
    }

    #[Test]
    public function testRuntimeInsertsWhenNoConflict(): void
    {
        $this->makeUpsertUsersTable();
        $this->database->insert('upsert_users')->values(['email' => 'a@b.c', 'name' => 'Old'])->run();

        $this->database->insert('upsert_users')
            ->values(['email' => 'x@y.z', 'name' => 'New'])
            ->onConflict('email')
            ->run();

        Assert::count($this->database->select()->from('upsert_users')->fetchAll(), 2);
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
