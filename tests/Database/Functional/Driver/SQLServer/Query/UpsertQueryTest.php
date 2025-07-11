<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpsertQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
final class UpsertQueryTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
    protected const QUERY_REQUIRES_CONFLICTS   = false;
    protected const QUERY_WITH_VALUES          = 'MERGE INTO [table] WITH (holdlock) AS [target] USING ( VALUES (?, ?) ) AS [source] ([email], [name]) ON [target].[email] = [source].[email] WHEN MATCHED THEN UPDATE SET [target].[email] = [source].[email], [target].[name] = [source].[name] WHEN NOT MATCHED THEN INSERT ([email], [name]) VALUES ([source].[email], [source].[name]);';
    protected const QUERY_WITH_STATES_VALUES   = 'MERGE INTO [table] WITH (holdlock) AS [target] USING ( VALUES (?, ?) ) AS [source] ([email], [name]) ON [target].[email] = [source].[email] WHEN MATCHED THEN UPDATE SET [target].[email] = [source].[email], [target].[name] = [source].[name] WHEN NOT MATCHED THEN INSERT ([email], [name]) VALUES ([source].[email], [source].[name]);';
    protected const QUERY_WITH_MULTIPLE_ROWS   = 'MERGE INTO [table] WITH (holdlock) AS [target] USING ( VALUES (?, ?), (?, ?) ) AS [source] ([email], [name]) ON [target].[email] = [source].[email] WHEN MATCHED THEN UPDATE SET [target].[email] = [source].[email], [target].[name] = [source].[name] WHEN NOT MATCHED THEN INSERT ([email], [name]) VALUES ([source].[email], [source].[name]);';
    protected const QUERY_WITH_EXPRESSIONS     = 'MERGE INTO [table] WITH (holdlock) AS [target] USING ( VALUES (?, ?, NOW(), NOW(), ?) ) AS [source] ([email], [name], [created_at], [updated_at], [deleted_at]) ON [target].[email] = [source].[email] WHEN MATCHED THEN UPDATE SET [target].[email] = [source].[email], [target].[name] = [source].[name], [target].[created_at] = [source].[created_at], [target].[updated_at] = [source].[updated_at], [target].[deleted_at] = [source].[deleted_at] WHEN NOT MATCHED THEN INSERT ([email], [name], [created_at], [updated_at], [deleted_at]) VALUES ([source].[email], [source].[name], [source].[created_at], [source].[updated_at], [source].[deleted_at]);';
    protected const QUERY_WITH_FRAGMENTS       = 'MERGE INTO [table] WITH (holdlock) AS [target] USING ( VALUES (?, ?, NOW(), datetime(\'now\'), ?) ) AS [source] ([email], [name], [created_at], [updated_at], [deleted_at]) ON [target].[email] = [source].[email] WHEN MATCHED THEN UPDATE SET [target].[email] = [source].[email], [target].[name] = [source].[name], [target].[created_at] = [source].[created_at], [target].[updated_at] = [source].[updated_at], [target].[deleted_at] = [source].[deleted_at] WHEN NOT MATCHED THEN INSERT ([email], [name], [created_at], [updated_at], [deleted_at]) VALUES ([source].[email], [source].[name], [source].[created_at], [source].[updated_at], [source].[deleted_at]);';
    protected const QUERY_WITH_CUSTOM_FRAGMENT = 'MERGE INTO [table] WITH (holdlock) AS [target] USING ( VALUES (?, ?, NOW()) ) AS [source] ([email], [name], [expired_at]) ON [target].[email] = [source].[email] WHEN MATCHED THEN UPDATE SET [target].[email] = [source].[email], [target].[name] = [source].[name], [target].[expired_at] = [source].[expired_at] WHEN NOT MATCHED THEN INSERT ([email], [name], [expired_at]) VALUES ([source].[email], [source].[name], [source].[expired_at]);';
}
