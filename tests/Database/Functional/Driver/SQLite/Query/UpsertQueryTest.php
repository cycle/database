<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Query;

use Cycle\Database\Injection\Expression;
use Cycle\Database\Query\OnConflict;
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
            'INSERT INTO {users} ({email}, {name}) VALUES (?, ?) ON CONFLICT ({email}) DO UPDATE SET {name} = {EXCLUDED}.{name}',
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
            . 'ON CONFLICT ({email}) DO UPDATE SET {name} = {EXCLUDED}.{name}, {visits} = {EXCLUDED}.{visits}',
            $q,
        );
    }

    public function testDoUpdateWithExpression(): void
    {
        $q = $this->database->insert('counters')
            ->values(['key' => 'x', 'n' => 1])
            ->onConflict(OnConflict::target('key')->doUpdate([
                'n' => new Expression('counters.n + EXCLUDED.n'),
            ]));

        $this->assertSameQuery(
            'INSERT INTO {counters} ({key}, {n}) VALUES (?, ?) '
            . 'ON CONFLICT ({key}) DO UPDATE SET {n} = {counters}.{n} + {EXCLUDED}.{n}',
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
            . 'ON CONFLICT ({email}) DO UPDATE SET {name} = {EXCLUDED}.{name}',
            $q,
        );
    }
}
