<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLServer\Query;

use Cycle\Database\Driver\Postgres\PostgresOnConflict;
use Cycle\Database\Driver\SQLServer\Query\SQLServerInsertQuery;
use Cycle\Database\Driver\SQLServer\SQLServerOnConflict;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Exception\CompilerException;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Query\ConflictAction;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Tests\Feature\Common\Query\UpsertQueryTest as CommonClass;
use Cycle\Testing\Database\Assert\SqlAssert;
use Testo\Expect;
use Testo\Test;

final class UpsertQueryTest extends CommonClass
{
    #[Test]
    public function testShorthandSingleColumn(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex'])
            ->onConflict('email');

        SqlAssert::same($q, 'MERGE INTO {users} WITH (HOLDLOCK) AS {target} '
        . 'USING (VALUES (?, ?)) AS {source} ({email}, {name}) '
        . 'ON {target}.{email} = {source}.{email} '
        . 'WHEN MATCHED THEN UPDATE SET {target}.{name} = {source}.{name} '
        . 'WHEN NOT MATCHED THEN INSERT ({email}, {name}) VALUES ({source}.{email}, {source}.{name});');
    }

    #[Test]
    public function testDoUpdateExplicitColumns(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex', 'visits' => 1])
            ->onConflict(OnConflict::target('email')->doUpdate(['name', 'visits']));

        SqlAssert::same($q, 'MERGE INTO {users} WITH (HOLDLOCK) AS {target} '
        . 'USING (VALUES (?, ?, ?)) AS {source} ({email}, {name}, {visits}) '
        . 'ON {target}.{email} = {source}.{email} '
        . 'WHEN MATCHED THEN UPDATE SET {target}.{name} = {source}.{name}, {target}.{visits} = {source}.{visits} '
        . 'WHEN NOT MATCHED THEN INSERT ({email}, {name}, {visits}) '
        . 'VALUES ({source}.{email}, {source}.{name}, {source}.{visits});');
    }

    #[Test]
    public function testDoUpdateWithExpression(): void
    {
        $q = $this->database->insert('counters')
            ->values(['key' => 'x', 'n' => 1])
            ->onConflict(OnConflict::target('key')->doUpdate([
                'n' => new Expression('target.n + source.n'),
            ]));

        SqlAssert::same($q, 'MERGE INTO {counters} WITH (HOLDLOCK) AS {target} '
        . 'USING (VALUES (?, ?)) AS {source} ({key}, {n}) '
        . 'ON {target}.{key} = {source}.{key} '
        . 'WHEN MATCHED THEN UPDATE SET {target}.{n} = {target}.{n} + {source}.{n} '
        . 'WHEN NOT MATCHED THEN INSERT ({key}, {n}) VALUES ({source}.{key}, {source}.{n});');
    }

    #[Test]
    public function testDoNothing(): void
    {
        $q = $this->database->insert('logs')
            ->values(['request_id' => 'r1', 'payload' => 'p'])
            ->onConflict(OnConflict::target('request_id')->doNothing());

        SqlAssert::same($q, 'MERGE INTO {logs} WITH (HOLDLOCK) AS {target} '
        . 'USING (VALUES (?, ?)) AS {source} ({request_id}, {payload}) '
        . 'ON {target}.{request_id} = {source}.{request_id} '
        . 'WHEN NOT MATCHED THEN INSERT ({request_id}, {payload}) VALUES ({source}.{request_id}, {source}.{payload});');
    }

    #[Test]
    public function testPostgresOnConflictRejected(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c'])
            ->onConflict(PostgresOnConflict::target('email')->doUpdate());

        Expect::exception(BuilderException::class);
        (string) $q;
    }

    #[Test]
    public function testUpsertWithReturningColumn(): void
    {
        /** @var SQLServerInsertQuery $q */
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex'])
            ->onConflict('email');
        $q->returning('id');

        SqlAssert::same($q, 'MERGE INTO {users} WITH (HOLDLOCK) AS {target} '
        . 'USING (VALUES (?, ?)) AS {source} ({email}, {name}) '
        . 'ON {target}.{email} = {source}.{email} '
        . 'WHEN MATCHED THEN UPDATE SET {target}.{name} = {source}.{name} '
        . 'WHEN NOT MATCHED THEN INSERT ({email}, {name}) VALUES ({source}.{email}, {source}.{name}) '
        . 'OUTPUT INSERTED.{id};');
    }

    #[Test]
    public function testUpsertWithMultipleReturningColumns(): void
    {
        /** @var SQLServerInsertQuery $q */
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex'])
            ->onConflict(OnConflict::target('email')->doNothing());
        $q->returning('id', 'name');

        SqlAssert::same($q, 'MERGE INTO {users} WITH (HOLDLOCK) AS {target} '
        . 'USING (VALUES (?, ?)) AS {source} ({email}, {name}) '
        . 'ON {target}.{email} = {source}.{email} '
        . 'WHEN NOT MATCHED THEN INSERT ({email}, {name}) VALUES ({source}.{email}, {source}.{name}) '
        . 'OUTPUT INSERTED.{id}, INSERTED.{name};');
    }

    #[Test]
    public function testEmptyConflictTargetIsRejected(): void
    {
        // OnConflict::target() rejects empty input, so the compiler's empty-target guard
        // is only reachable if an instance is constructed bypassing that validation.
        $ref = new \ReflectionClass(SQLServerOnConflict::class);
        $onConflict = $ref->newInstanceWithoutConstructor();
        foreach (['target' => [], 'action' => ConflictAction::Update, 'update' => null] as $prop => $value) {
            $p = new \ReflectionProperty(OnConflict::class, $prop);
            $p->setValue($onConflict, $value);
        }

        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c'])
            ->onConflict($onConflict);

        Expect::exception(CompilerException::class)->withMessageContaining('Upsert query must define a conflict target.');
        (string) $q;
    }

    #[Test]
    public function testUpsertWithReturningFragment(): void
    {
        /** @var SQLServerInsertQuery $q */
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex'])
            ->onConflict('email');
        $q->returning(new Fragment('INSERTED.[name] as [full_name]'));

        SqlAssert::same($q, 'MERGE INTO {users} WITH (HOLDLOCK) AS {target} '
        . 'USING (VALUES (?, ?)) AS {source} ({email}, {name}) '
        . 'ON {target}.{email} = {source}.{email} '
        . 'WHEN MATCHED THEN UPDATE SET {target}.{name} = {source}.{name} '
        . 'WHEN NOT MATCHED THEN INSERT ({email}, {name}) VALUES ({source}.{email}, {source}.{name}) '
        . 'OUTPUT INSERTED.{name} as {full_name};');
    }
}
