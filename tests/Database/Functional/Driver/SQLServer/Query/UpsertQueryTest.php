<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Query;

// phpcs:ignore
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpsertQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
final class UpsertQueryTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
    protected const QUERY_REQUIRES_CONFLICTS      = true;
    protected const QUERY_WITH_VALUES             = 'MERGE INTO [table] WITH (holdlock) AS [target] USING ( VALUES (?, ?) ) AS [source] ([email], [name]) ON [target].[email] = [source].[email] WHEN MATCHED THEN UPDATE SET [target].[email] = [source].[email], [target].[name] = [source].[name] WHEN NOT MATCHED THEN INSERT ([email], [name]) VALUES ([source].[email], [source].[name]);';
    protected const QUERY_WITH_STATES_VALUES      = 'MERGE INTO [table] WITH (holdlock) AS [target] USING ( VALUES (?, ?) ) AS [source] ([email], [name]) ON [target].[email] = [source].[email] WHEN MATCHED THEN UPDATE SET [target].[email] = [source].[email], [target].[name] = [source].[name] WHEN NOT MATCHED THEN INSERT ([email], [name]) VALUES ([source].[email], [source].[name]);';
    protected const QUERY_WITH_MULTIPLE_ROWS      = 'MERGE INTO [table] WITH (holdlock) AS [target] USING ( VALUES (?, ?), (?, ?) ) AS [source] ([email], [name]) ON [target].[email] = [source].[email] WHEN MATCHED THEN UPDATE SET [target].[email] = [source].[email], [target].[name] = [source].[name] WHEN NOT MATCHED THEN INSERT ([email], [name]) VALUES ([source].[email], [source].[name]);';
    protected const QUERY_WITH_EXPRESSIONS        = 'MERGE INTO [table] WITH (holdlock) AS [target] USING ( VALUES (?, ?, NOW(), NOW(), ?) ) AS [source] ([email], [name], [created_at], [updated_at], [deleted_at]) ON [target].[email] = [source].[email] WHEN MATCHED THEN UPDATE SET [target].[email] = [source].[email], [target].[name] = [source].[name], [target].[created_at] = [source].[created_at], [target].[updated_at] = [source].[updated_at], [target].[deleted_at] = [source].[deleted_at] WHEN NOT MATCHED THEN INSERT ([email], [name], [created_at], [updated_at], [deleted_at]) VALUES ([source].[email], [source].[name], [source].[created_at], [source].[updated_at], [source].[deleted_at]);';
    protected const QUERY_WITH_FRAGMENTS          = 'MERGE INTO [table] WITH (holdlock) AS [target] USING ( VALUES (?, ?, NOW(), datetime(\'now\'), ?) ) AS [source] ([email], [name], [created_at], [updated_at], [deleted_at]) ON [target].[email] = [source].[email] WHEN MATCHED THEN UPDATE SET [target].[email] = [source].[email], [target].[name] = [source].[name], [target].[created_at] = [source].[created_at], [target].[updated_at] = [source].[updated_at], [target].[deleted_at] = [source].[deleted_at] WHEN NOT MATCHED THEN INSERT ([email], [name], [created_at], [updated_at], [deleted_at]) VALUES ([source].[email], [source].[name], [source].[created_at], [source].[updated_at], [source].[deleted_at]);';
    protected const QUERY_WITH_CUSTOM_FRAGMENT    = 'MERGE INTO [table] WITH (holdlock) AS [target] USING ( VALUES (?, ?, NOW()) ) AS [source] ([email], [name], [expired_at]) ON [target].[email] = [source].[email] WHEN MATCHED THEN UPDATE SET [target].[email] = [source].[email], [target].[name] = [source].[name], [target].[expired_at] = [source].[expired_at] WHEN NOT MATCHED THEN INSERT ([email], [name], [expired_at]) VALUES ([source].[email], [source].[name], [source].[expired_at]);';
    protected const QUERY_WITH_RETURNING_FRAGMENT = 'MERGE INTO [table] WITH (holdlock) AS [target] USING ( VALUES (?, ?, ?, NOW(), datetime(\'now\'), ?) ) AS [source] ([email], [name], [balance], [created_at], [updated_at], [deleted_at]) ON [target].[email] = [source].[email] WHEN MATCHED THEN UPDATE SET [target].[email] = [source].[email], [target].[name] = [source].[name], [target].[balance] = [source].[balance], [target].[created_at] = [source].[created_at], [target].[updated_at] = [source].[updated_at], [target].[deleted_at] = [source].[deleted_at] WHEN NOT MATCHED THEN INSERT ([email], [name], [balance], [created_at], [updated_at], [deleted_at]) VALUES ([source].[email], [source].[name], [source].[balance], [source].[created_at], [source].[updated_at], [source].[deleted_at]) OUTPUT INSERTED.[email], {balance} + 100 AS {modified_balance};';

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
            ])->returning('email', new Fragment('[balance] + 100 AS [modified_balance]'));

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
        $this->assertSame('100', $result['balance']);
    }

    public function testEmptyStringReturnedWithoutPrimaryKeyAndReturningValues(): void
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

        $this->assertSame('', $result);
        $this->assertEquals(
            [
                ['email' => 'adam@email.com', 'name' => 'Adam', 'balance' => 100],
            ],
            $table->select()->fetchAll(),
        );
    }
}
