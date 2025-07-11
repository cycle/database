<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

// phpcs:ignore
use Cycle\Database\Driver\Postgres\Query\PostgresUpsertQuery;
use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpsertQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class UpsertQueryTest extends CommonClass
{
    public const DRIVER                           = 'postgres';
    protected const QUERY_INSTANCE                = PostgresUpsertQuery::class;
    protected const QUERY_WITH_RETURNING_FRAGMENT = 'INSERT INTO {table} ({email}, {name}, {balance}, {created_at}, {updated_at}, {deleted_at}) VALUES (?, ?, ?, NOW(), datetime(\'now\'), ?) ON CONFLICT ({email}) DO UPDATE SET {email} = EXCLUDED.{email}, {name} = EXCLUDED.{name}, {balance} = EXCLUDED.{balance}, {created_at} = EXCLUDED.{created_at}, {updated_at} = EXCLUDED.{updated_at}, {deleted_at} = EXCLUDED.{deleted_at} RETURNING {balance} + 100 as {modified_balance}';

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

        $this->assertSameQuery(static::QUERY_WITH_RETURNING_FRAGMENT, $upsert);
        $this->assertSameParameters(['adam@email.com', 'Adam', 100, null], $upsert);
    }

    public function testReturningSingleValueFromDatabase(): void
    {
        $schema = $this->schema('foo');
        $schema->primary('id');
        $schema->string('name')->nullable(false);
        $schema->string('email', 64)->nullable(false);
        $schema->integer('balance')->defaultValue(0);
        $schema->index(['email'])->unique(true);
        $schema->save();

        $table = $this->database->table('foo');

        $this->assertTrue($table->exists());
        $this->assertSame(0, $table->count());

        $email = $table->upsert()
            ->conflicts('email')
            ->values([
                'email' => 'adam@email.com',
                'name' => 'Adam',
                'balance' => 100,
            ])
            ->returning('email')
            ->run();

        $this->assertSame('adam@email.com', $email);
    }

    public function testReturningMultipleValuesFromDatabase(): void
    {
        $schema = $this->schema('foo');
        $schema->primary('id');
        $schema->string('name')->nullable(false);
        $schema->string('email', 64)->nullable(false);
        $schema->integer('balance')->defaultValue(0);
        $schema->index(['email'])->unique(true);
        $schema->save();

        $table = $this->database->table('foo');

        $this->assertTrue($table->exists());
        $this->assertSame(0, $table->count());

        $result = $table->upsert()
            ->conflicts('email')
            ->values([
                'email' => 'adam@email.com',
                'name' => 'Adam',
                'balance' => 100,
            ])
            ->returning('email', 'name', 'balance')
            ->run();

        $this->assertSame('adam@email.com', $result['email']);
        $this->assertSame('Adam', $result['name']);
        $this->assertSame(100, $result['balance']);
    }

    public function testNullReturnedWithoutPrimaryKeyAndReturningValues(): void
    {
        $schema = $this->schema('bar');
        $schema->string('name')->nullable(false);
        $schema->string('email', 64)->nullable(false);
        $schema->integer('balance')->defaultValue(0);
        $schema->index(['email'])->unique(true);
        $schema->save();

        $table = $this->database->table('bar');

        $this->assertTrue($table->exists());
        $this->assertSame(0, $table->count());

        $result = $table->upsert()
            ->conflicts('email')
            ->values([
                'email' => 'adam@email.com',
                'name' => 'Adam',
                'balance' => 100,
            ])
            ->run();

        $this->assertNull($result);
        $this->assertEquals(
            [
                ['email' => 'adam@email.com', 'name' => 'Adam', 'balance' => 100],
            ],
            $table->select()->fetchAll(),
        );
    }
}
