<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Query;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\SQLite\SQLiteOnConflict;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Query\OnConflictWhere;
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpsertQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class UpsertQueryTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    public function testShorthandSingleColumn(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex'])
            ->onConflict('email');

        $this->assertSameQuery(
            'INSERT INTO {users} ({email}, {name}) VALUES (?, ?) ON CONFLICT ({email}) DO UPDATE SET {name} = EXCLUDED.{name}',
            $q,
        );
    }

    public function testDoUpdateExplicitColumns(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex', 'visits' => 1])
            ->onConflict(OnConflict::target('email')->doUpdate(['name', 'visits']));

        $this->assertSameQuery(
            'INSERT INTO {users} ({email}, {name}, {visits}) VALUES (?, ?, ?) '
            . 'ON CONFLICT ({email}) DO UPDATE SET {name} = EXCLUDED.{name}, {visits} = EXCLUDED.{visits}',
            $q,
        );
    }

    public function testDoUpdateWithFragmentReferencingExcluded(): void
    {
        // EXCLUDED must stay unquoted, so reference it with a raw Fragment (not an
        // Expression, which would quote it — tolerated by SQLite but broken on Postgres).
        $q = $this->database->insert('counters')
            ->values(['key' => 'x', 'n' => 1])
            ->onConflict(OnConflict::target('key')->doUpdate([
                'n' => new Fragment('counters.n + EXCLUDED.n'),
            ]));

        $this->assertSameQuery(
            'INSERT INTO {counters} ({key}, {n}) VALUES (?, ?) '
            . 'ON CONFLICT ({key}) DO UPDATE SET {n} = counters.n + EXCLUDED.n',
            $q,
        );
    }

    public function testDoNothing(): void
    {
        $q = $this->database->insert('logs')
            ->values(['request_id' => 'r1', 'payload' => 'p'])
            ->onConflict(OnConflict::target('request_id')->doNothing());

        $this->assertSameQuery(
            'INSERT INTO {logs} ({request_id}, {payload}) VALUES (?, ?) ON CONFLICT ({request_id}) DO NOTHING',
            $q,
        );
    }

    public function testMultiRowUpsert(): void
    {
        $q = $this->database->insert('users')
            ->values([
                ['email' => 'a@b.c', 'name' => 'Alex'],
                ['email' => 'b@c.d', 'name' => 'Bob'],
            ])
            ->onConflict('email');

        $this->assertSameQuery(
            'INSERT INTO {users} ({email}, {name}) VALUES (?, ?), (?, ?) '
            . 'ON CONFLICT ({email}) DO UPDATE SET {name} = EXCLUDED.{name}',
            $q,
        );
    }

    public function testTargetWherePartialIndex(): void
    {
        $q = $this->database->insert('routes')
            ->values(['resource_key' => 'k', 'route' => '/r', 'body' => 'b'])
            ->onConflict(
                SQLiteOnConflict::target('resource_key', 'route')
                    ->targetWhere('resource_key IS NOT NULL')
                    ->doUpdate(['body']),
            );

        $this->assertSameQuery(
            'INSERT INTO {routes} ({resource_key}, {route}, {body}) VALUES (?, ?, ?) '
            . 'ON CONFLICT ({resource_key}, {route}) WHERE resource_key IS NOT NULL '
            . 'DO UPDATE SET {body} = EXCLUDED.{body}',
            $q,
        );
    }

    public function testTargetWhereClosureBuilder(): void
    {
        $q = $this->database->insert('routes')
            ->values(['resource_key' => 'k', 'route' => '/r', 'body' => 'b'])
            ->onConflict(
                SQLiteOnConflict::target('resource_key', 'route')
                    ->targetWhere(static fn(OnConflictWhere $w) => $w->where('resource_key', '!=', null))
                    ->doUpdate(['body']),
            );

        $this->assertSameQuery(
            'INSERT INTO {routes} ({resource_key}, {route}, {body}) VALUES (?, ?, ?) '
            . 'ON CONFLICT ({resource_key}, {route}) WHERE {resource_key} IS NOT NULL '
            . 'DO UPDATE SET {body} = EXCLUDED.{body}',
            $q,
        );
    }

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

        $this->assertSameQueryWithParameters(
            'INSERT INTO {routes} ({resource_key}, {route}, {priority}) VALUES (?, ?, ?) '
            . 'ON CONFLICT ({resource_key}, {route}) WHERE {priority} > ? '
            . 'DO UPDATE SET {priority} = {routes}.{priority} + ?',
            ['k', '/r', 1, 5, 10],
            $q,
        );
    }

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
        $this->assertCount(1, $rows);
        $this->assertSame('new', $rows[0]['body']);

        // Different key → no conflict → new row inserted.
        $upsert('k2', 'second');
        $this->assertCount(2, $this->database->select()->from('upsert_routes')->fetchAll());
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
