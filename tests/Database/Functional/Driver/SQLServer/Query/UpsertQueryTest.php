<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Query;

use Cycle\Database\Driver\Postgres\PostgresOnConflict;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpsertQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class UpsertQueryTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    public function testShorthandSingleColumn(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex'])
            ->onConflict('email');

        $this->assertSameQuery(
            'MERGE INTO {users} WITH (HOLDLOCK) AS {target} '
            . 'USING (VALUES (?, ?)) AS {source} ({email}, {name}) '
            . 'ON {target}.{email} = {source}.{email} '
            . 'WHEN MATCHED THEN UPDATE SET {target}.{name} = {source}.{name} '
            . 'WHEN NOT MATCHED THEN INSERT ({email}, {name}) VALUES ({source}.{email}, {source}.{name});',
            $q,
        );
    }

    public function testDoUpdateExplicitColumns(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c', 'name' => 'Alex', 'visits' => 1])
            ->onConflict(OnConflict::target('email')->doUpdate(['name', 'visits']));

        $this->assertSameQuery(
            'MERGE INTO {users} WITH (HOLDLOCK) AS {target} '
            . 'USING (VALUES (?, ?, ?)) AS {source} ({email}, {name}, {visits}) '
            . 'ON {target}.{email} = {source}.{email} '
            . 'WHEN MATCHED THEN UPDATE SET {target}.{name} = {source}.{name}, {target}.{visits} = {source}.{visits} '
            . 'WHEN NOT MATCHED THEN INSERT ({email}, {name}, {visits}) '
            . 'VALUES ({source}.{email}, {source}.{name}, {source}.{visits});',
            $q,
        );
    }

    public function testDoUpdateWithExpression(): void
    {
        $q = $this->database->insert('counters')
            ->values(['key' => 'x', 'n' => 1])
            ->onConflict(OnConflict::target('key')->doUpdate([
                'n' => new Expression('target.n + source.n'),
            ]));

        $this->assertSameQuery(
            'MERGE INTO {counters} WITH (HOLDLOCK) AS {target} '
            . 'USING (VALUES (?, ?)) AS {source} ({key}, {n}) '
            . 'ON {target}.{key} = {source}.{key} '
            . 'WHEN MATCHED THEN UPDATE SET {target}.{n} = {target}.{n} + {source}.{n} '
            . 'WHEN NOT MATCHED THEN INSERT ({key}, {n}) VALUES ({source}.{key}, {source}.{n});',
            $q,
        );
    }

    public function testDoNothing(): void
    {
        $q = $this->database->insert('logs')
            ->values(['request_id' => 'r1', 'payload' => 'p'])
            ->onConflict(OnConflict::target('request_id')->doNothing());

        $this->assertSameQuery(
            'MERGE INTO {logs} WITH (HOLDLOCK) AS {target} '
            . 'USING (VALUES (?, ?)) AS {source} ({request_id}, {payload}) '
            . 'ON {target}.{request_id} = {source}.{request_id} '
            . 'WHEN NOT MATCHED THEN INSERT ({request_id}, {payload}) VALUES ({source}.{request_id}, {source}.{payload});',
            $q,
        );
    }

    public function testPostgresOnConflictRejected(): void
    {
        $q = $this->database->insert('users')
            ->values(['email' => 'a@b.c'])
            ->onConflict(PostgresOnConflict::target('email')->doUpdate());

        $this->expectException(BuilderException::class);
        (string) $q;
    }
}
