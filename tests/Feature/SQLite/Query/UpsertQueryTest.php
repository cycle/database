<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLite\Query;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\SQLite\Query\SQLiteInsertQuery;
use Cycle\Database\Driver\SQLite\SQLiteOnConflict;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Query\OnConflictWhere;
use Cycle\Database\Tests\Feature\Common\Query\UpsertQueryTest as CommonClass;
use Cycle\Testing\Database\Assert\SqlAssert;
use Testo\Assert;
use Testo\Test;

final class UpsertQueryTest extends CommonClass
{
    #[Test]
    public function testShorthandSingleColumn(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex'])
            ->onConflict('email');

        SqlAssert::same($q, 'INSERT INTO {users} ({email}, {name}) VALUES (?, ?) ON CONFLICT ({email}) DO UPDATE SET {name} = EXCLUDED.{name}');
    }

    #[Test]
    public function testDoUpdateExplicitColumns(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex', 'visits' => 1])
            ->onConflict(OnConflict::target('email')->doUpdate(['name', 'visits']));

        SqlAssert::same($q, 'INSERT INTO {users} ({email}, {name}, {visits}) VALUES (?, ?, ?) '
        . 'ON CONFLICT ({email}) DO UPDATE SET {name} = EXCLUDED.{name}, {visits} = EXCLUDED.{visits}');
    }

    #[Test]
    public function testDoUpdateWithFragmentReferencingExcluded(): void
    {
        // EXCLUDED must stay unquoted, so reference it with a raw Fragment (not an
        // Expression, which would quote it — tolerated by SQLite but broken on Postgres).
        $q = $this->database->insert('counters')
            ->values(['key' => 'x', 'n' => 1])
            ->onConflict(OnConflict::target('key')->doUpdate([
                'n' => new Fragment('counters.n + EXCLUDED.n'),
            ]));

        SqlAssert::same($q, 'INSERT INTO {counters} ({key}, {n}) VALUES (?, ?) '
        . 'ON CONFLICT ({key}) DO UPDATE SET {n} = counters.n + EXCLUDED.n');
    }

    #[Test]
    public function testDoNothing(): void
    {
        $q = $this->database->insert('logs')
            ->values(['request_id' => 'r1', 'payload' => 'p'])
            ->onConflict(OnConflict::target('request_id')->doNothing());

        SqlAssert::same($q, 'INSERT INTO {logs} ({request_id}, {payload}) VALUES (?, ?) ON CONFLICT ({request_id}) DO NOTHING');
    }

    #[Test]
    public function testMultiRowUpsert(): void
    {
        $q = $this->database->insert('users')
            ->values([
                ['email' => 'a@b.c', 'name' => 'Alex'],
                ['email' => 'b@c.d', 'name' => 'Bob'],
            ])
            ->onConflict('email');

        SqlAssert::same($q, 'INSERT INTO {users} ({email}, {name}) VALUES (?, ?), (?, ?) '
        . 'ON CONFLICT ({email}) DO UPDATE SET {name} = EXCLUDED.{name}');
    }

    #[Test]
    public function testTargetWherePartialIndex(): void
    {
        $q = $this->database->insert('routes')
            ->values(['resource_key' => 'k', 'route' => '/r', 'body' => 'b'])
            ->onConflict(
                SQLiteOnConflict::target('resource_key', 'route')
                    ->targetWhere('resource_key IS NOT NULL')
                    ->doUpdate(['body']),
            );

        SqlAssert::same($q, 'INSERT INTO {routes} ({resource_key}, {route}, {body}) VALUES (?, ?, ?) '
        . 'ON CONFLICT ({resource_key}, {route}) WHERE resource_key IS NOT NULL '
        . 'DO UPDATE SET {body} = EXCLUDED.{body}');
    }

    #[Test]
    public function testTargetWhereClosureBuilder(): void
    {
        $q = $this->database->insert('routes')
            ->values(['resource_key' => 'k', 'route' => '/r', 'body' => 'b'])
            ->onConflict(
                SQLiteOnConflict::target('resource_key', 'route')
                    ->targetWhere(static fn(OnConflictWhere $w) => $w->where('resource_key', '!=', null))
                    ->doUpdate(['body']),
            );

        SqlAssert::same($q, 'INSERT INTO {routes} ({resource_key}, {route}, {body}) VALUES (?, ?, ?) '
        . 'ON CONFLICT ({resource_key}, {route}) WHERE {resource_key} IS NOT NULL '
        . 'DO UPDATE SET {body} = EXCLUDED.{body}');
    }

    #[Test]
    public function testTargetWhereParameterOrder(): void
    {
        // Values, then the predicate parameter, then the DO UPDATE parameter — the
        // predicate is rendered between the conflict target and DO UPDATE, so its
        // parameter must bind before the update parameter (also on the cached path).
        $q = $this->database->insert('routes')
            ->values(['resource_key' => 'k', 'route' => '/r', 'priority' => 1])
            ->onConflict(
                SQLiteOnConflict::target('resource_key', 'route')
                    ->targetWhere(static fn(OnConflictWhere $w) => $w->where('priority', '>', 5))
                    ->doUpdate(['priority' => new Expression('routes.priority + ?', 10)]),
            );

        SqlAssert::sameWithParameters($q, 'INSERT INTO {routes} ({resource_key}, {route}, {priority}) VALUES (?, ?, ?) '
        . 'ON CONFLICT ({resource_key}, {route}) WHERE {priority} > ? '
        . 'DO UPDATE SET {priority} = {routes}.{priority} + ?', ['k', '/r', 1, 5, 10]);
    }

    #[Test]
    public function testTargetWherePartialIndexRuntime(): void
    {
        $this->makePartialIndexTable();

        $this->database->insert('upsert_routes')
            ->values(['resource_key' => 'k', 'route' => '/r', 'body' => 'old'])->run();

        $upsert = fn(string $key, string $body) => $this->database->insert('upsert_routes')
            ->values(['resource_key' => $key, 'route' => '/r', 'body' => $body])
            ->onConflict(
                SQLiteOnConflict::target('resource_key', 'route')
                    ->targetWhere('resource_key IS NOT NULL')
                    ->doUpdate(['body']),
            )->run();

        // Matching partial index → conflict → update in place.
        $upsert('k', 'new');
        $rows = $this->database->select()->from('upsert_routes')->fetchAll();
        Assert::count($rows, 1);
        Assert::same($rows[0]['body'], 'new');

        // Different key → no conflict → new row inserted.
        $upsert('k2', 'second');
        Assert::count($this->database->select()->from('upsert_routes')->fetchAll(), 2);
    }

    #[Test]
    public function testUpsertWithReturningColumn(): void
    {
        /** @var SQLiteInsertQuery $q */
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex'])
            ->onConflict('email');
        $q->returning('id');

        SqlAssert::same($q, 'INSERT INTO {users} ({email}, {name}) VALUES (?, ?) '
        . 'ON CONFLICT ({email}) DO UPDATE SET {name} = EXCLUDED.{name} RETURNING {id}');
    }

    #[Test]
    public function testUpsertWithMultipleReturningColumns(): void
    {
        /** @var SQLiteInsertQuery $q */
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex'])
            ->onConflict(OnConflict::target('email')->doNothing());
        $q->returning('id', 'name');

        SqlAssert::same($q, 'INSERT INTO {users} ({email}, {name}) VALUES (?, ?) '
        . 'ON CONFLICT ({email}) DO NOTHING RETURNING {id}, {name}');
    }

    private function makePartialIndexTable(): void
    {
        $schema = $this->schema('upsert_routes');
        $schema->primary('id');
        $schema->string('resource_key')->nullable(true);
        $schema->string('route');
        $schema->string('body');
        $schema->save(Handler::DO_ALL);

        $this->database->execute(
            'CREATE UNIQUE INDEX upsert_routes_partial ON upsert_routes (resource_key, route) '
            . 'WHERE resource_key IS NOT NULL',
        );
    }
}
