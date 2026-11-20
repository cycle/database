<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Query;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\Postgres\PostgresOnConflict;
use Cycle\Database\Exception\CompilerException;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Query\OnConflictWhere;
use Cycle\Database\Tests\Feature\Common\Query\UpsertQueryTest as CommonClass;
use Cycle\Testing\Database\Assert\SqlAssert;
use Testo\Assert;
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

        SqlAssert::same($q, 'INSERT INTO {users} ({email}, {name}) VALUES (?, ?) '
        . 'ON CONFLICT ({email}) DO UPDATE SET {name} = EXCLUDED.{name}');
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
        // Expression, which would quote it and break at runtime on Postgres).
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
    public function testConstraintTarget(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex'])
            ->onConflict(PostgresOnConflict::onConstraint('users_email_unique')->doUpdate(['name']));

        SqlAssert::same($q, 'INSERT INTO {users} ({email}, {name}) VALUES (?, ?) '
        . 'ON CONFLICT ON CONSTRAINT {users_email_unique} DO UPDATE SET {name} = EXCLUDED.{name}');
    }

    #[Test]
    public function testTargetWherePartialIndex(): void
    {
        $q = $this->database->insert('routes')
            ->values(['resource_key' => 'k', 'route' => '/r', 'body' => 'b'])
            ->onConflict(
                PostgresOnConflict::target('resource_key', 'route')
                    ->targetWhere('resource_key IS NOT NULL')
                    ->doUpdate(['body']),
            );

        SqlAssert::same($q, 'INSERT INTO {routes} ({resource_key}, {route}, {body}) VALUES (?, ?, ?) '
        . 'ON CONFLICT ({resource_key}, {route}) WHERE resource_key IS NOT NULL '
        . 'DO UPDATE SET {body} = EXCLUDED.{body}');
    }

    #[Test]
    public function testTargetWhereVariadicCondition(): void
    {
        $q = $this->database->insert('routes')
            ->values(['resource_key' => 'k', 'route' => '/r', 'priority' => 1])
            ->onConflict(
                PostgresOnConflict::target('resource_key', 'route')
                    ->targetWhere('priority', '>', 5)
                    ->doUpdate(['priority']),
            );

        SqlAssert::sameWithParameters($q, 'INSERT INTO {routes} ({resource_key}, {route}, {priority}) VALUES (?, ?, ?) '
        . 'ON CONFLICT ({resource_key}, {route}) WHERE {priority} > ? '
        . 'DO UPDATE SET {priority} = EXCLUDED.{priority}', ['k', '/r', 1, 5]);
    }

    #[Test]
    public function testTargetWhereClosureBuilder(): void
    {
        $q = $this->database->insert('routes')
            ->values(['resource_key' => 'k', 'route' => '/r', 'body' => 'b'])
            ->onConflict(
                PostgresOnConflict::target('resource_key', 'route')
                    ->targetWhere(static fn(OnConflictWhere $w) => $w->where('resource_key', '!=', null))
                    ->doUpdate(['body']),
            );

        SqlAssert::same($q, 'INSERT INTO {routes} ({resource_key}, {route}, {body}) VALUES (?, ?, ?) '
        . 'ON CONFLICT ({resource_key}, {route}) WHERE {resource_key} IS NOT NULL '
        . 'DO UPDATE SET {body} = EXCLUDED.{body}');
    }

    #[Test]
    public function testTargetWhereMultiRow(): void
    {
        // Multi-row upsert is not cached, so the predicate is rendered on the
        // direct compile path (bypassing the cache's where-hasher).
        $q = $this->database->insert('routes')
            ->values([
                ['resource_key' => 'k1', 'route' => '/a', 'body' => 'b1'],
                ['resource_key' => 'k2', 'route' => '/b', 'body' => 'b2'],
            ])
            ->onConflict(
                PostgresOnConflict::target('resource_key', 'route')
                    ->targetWhere('priority', '>', 5)
                    ->doUpdate(['body']),
            );

        SqlAssert::sameWithParameters($q, 'INSERT INTO {routes} ({resource_key}, {route}, {body}) VALUES (?, ?, ?), (?, ?, ?) '
        . 'ON CONFLICT ({resource_key}, {route}) WHERE {priority} > ? '
        . 'DO UPDATE SET {body} = EXCLUDED.{body}', ['k1', '/a', 'b1', 'k2', '/b', 'b2', 5]);
    }

    #[Test]
    public function testConstraintWithPredicateRejected(): void
    {
        $q = $this->database->insert('routes')
            ->values(['resource_key' => 'k', 'route' => '/r'])
            ->onConflict(
                PostgresOnConflict::onConstraint('routes_unique')
                    ->targetWhere('resource_key IS NOT NULL')
                    ->doUpdate(['route']),
            );

        Expect::exception(CompilerException::class);
        (string) $q;
    }

    #[Test]
    public function testTargetWherePartialIndexRuntime(): void
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

        $this->database->insert('upsert_routes')
            ->values(['resource_key' => 'k', 'route' => '/r', 'body' => 'old'])->run();

        $upsert = fn(string $key, string $body) => $this->database->insert('upsert_routes')
            ->values(['resource_key' => $key, 'route' => '/r', 'body' => $body])
            ->onConflict(
                PostgresOnConflict::target('resource_key', 'route')
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
    public function testDoUpdateFragmentExcludedRuntime(): void
    {
        // Locks the documented EXCLUDED-via-Fragment path: an Expression here would
        // emit a quoted "EXCLUDED" and fail on Postgres at runtime.
        $schema = $this->schema('upsert_counters');
        $schema->primary('id');
        $schema->string('k');
        $schema->integer('n');
        $schema->index(['k'])->unique(true);
        $schema->save(Handler::DO_ALL);

        $this->database->insert('upsert_counters')->values(['k' => 'x', 'n' => 10])->run();

        $this->database->insert('upsert_counters')
            ->values(['k' => 'x', 'n' => 5])
            ->onConflict(OnConflict::target('k')->doUpdate([
                'n' => new Fragment('upsert_counters.n + EXCLUDED.n'),
            ]))
            ->run();

        $rows = $this->database->select()->from('upsert_counters')->fetchAll();
        Assert::count($rows, 1);
        Assert::same((int) $rows[0]['n'], 15);
    }
}
