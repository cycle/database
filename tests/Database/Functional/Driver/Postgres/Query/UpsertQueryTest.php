<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

// phpcs:ignore
use Cycle\Database\Driver\Postgres\Query\PostgresUpsertQuery;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpsertQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class UpsertQueryTest extends CommonClass
{
    public const DRIVER = 'postgres';
    protected const QUERY_INSTANCE = PostgresUpsertQuery::class;
    protected const QUERY_WITH_FRAGMENTS_RETURNING = 'INSERT INTO {table} ({email}, {name}, {balance}, {created_at}, {updated_at}, {deleted_at}) VALUES (?, ?, ?, NOW(), datetime(\'now\'), ?) ON CONFLICT ({email}) DO UPDATE SET {email} = EXCLUDED.{email}, {name} = EXCLUDED.{name}, {balance} = EXCLUDED.{balance}, {created_at} = EXCLUDED.{created_at}, {updated_at} = EXCLUDED.{updated_at}, {deleted_at} = EXCLUDED.{deleted_at} RETURNING {balance} + 100 as {modified_balance}';

    public function testQueryWithFragmentsAndReturning(): void
    {
        $upsert = $this->database->upsert('table')
            ->conflicts('email')
            ->values([
                'email' => 'adam@email.com',
                'name' => 'Adam',
                'balance' => 100,
                'created_at' => new Fragment('NOW()'),
                'updated_at' => new Fragment('datetime(\'now\')'),
                'deleted_at' => null,
            ])->returning(new Fragment('"balance" + 100 as "modified_balance"'));

        $this->assertSameQuery(static::QUERY_WITH_FRAGMENTS_RETURNING, $upsert);
        $this->assertSameParameters(['adam@email.com', 'Adam', 100, null], $upsert);
    }
}
