<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Query;

use Cycle\Database\Driver\MySQL\MySQLOnConflict;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpsertQueryTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class UpsertQueryTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testShorthandSingleColumn(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex'])
            ->onConflict('email');

        $this->assertSameQuery(
            'INSERT INTO {users} ({email}, {name}) VALUES (?, ?) AS {new_row} '
            . 'ON DUPLICATE KEY UPDATE {name} = {new_row}.{name}',
            $q,
        );
    }

    #[\Testo\Test]
    public function testDoUpdateExplicitColumns(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex', 'visits' => 1])
            ->onConflict(OnConflict::target('email')->doUpdate(['name', 'visits']));

        $this->assertSameQuery(
            'INSERT INTO {users} ({email}, {name}, {visits}) VALUES (?, ?, ?) AS {new_row} '
            . 'ON DUPLICATE KEY UPDATE {name} = {new_row}.{name}, {visits} = {new_row}.{visits}',
            $q,
        );
    }

    #[\Testo\Test]
    public function testDoUpdateWithExpression(): void
    {
        $q = $this->database->insert('counters')
            ->values(['key' => 'x', 'n' => 1])
            ->onConflict(OnConflict::target('key')->doUpdate([
                'n' => new Expression('counters.n + new_row.n'),
            ]));

        $this->assertSameQuery(
            'INSERT INTO {counters} ({key}, {n}) VALUES (?, ?) AS {new_row} '
            . 'ON DUPLICATE KEY UPDATE {n} = {counters}.{n} + {new_row}.{n}',
            $q,
        );
    }

    #[\Testo\Test]
    public function testDoNothingEmulated(): void
    {
        $q = $this->database->insert('logs')
            ->values(['request_id' => 'r1', 'payload' => 'p'])
            ->onConflict(OnConflict::target('request_id')->doNothing());

        $this->assertSameQuery(
            'INSERT INTO {logs} ({request_id}, {payload}) VALUES (?, ?) '
            . 'ON DUPLICATE KEY UPDATE {request_id} = {request_id}',
            $q,
        );
    }

    #[\Testo\Test]
    public function testDoNothingUsesTargetWhenItIsNotFirstColumn(): void
    {
        // payload comes first in the inserted column list; the conventional no-op
        // self-assignment should still reference the conflict target column, not
        // the first inserted one.
        $q = $this->database->insert('logs')
            ->values(['payload' => 'p', 'request_id' => 'r1'])
            ->onConflict(OnConflict::target('request_id')->doNothing());

        $this->assertSameQuery(
            'INSERT INTO {logs} ({payload}, {request_id}) VALUES (?, ?) '
            . 'ON DUPLICATE KEY UPDATE {request_id} = {request_id}',
            $q,
        );
    }

    #[\Testo\Test]
    public function testCustomRowAlias(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex'])
            ->onConflict(MySQLOnConflict::target('email')->withRowAlias('updated')->doUpdate(['name']));

        $this->assertSameQuery(
            'INSERT INTO {users} ({email}, {name}) VALUES (?, ?) AS {updated} '
            . 'ON DUPLICATE KEY UPDATE {name} = {updated}.{name}',
            $q,
        );
    }

    #[\Testo\Test]
    public function testPostgresOnConflictRejected(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c'])
            ->onConflict(\Cycle\Database\Driver\Postgres\PostgresOnConflict::target('email')->doUpdate());

        \Testo\Expect::exception(BuilderException::class);
        (string) $q;
    }
}
