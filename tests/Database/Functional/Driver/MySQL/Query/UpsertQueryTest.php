<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpsertQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class UpsertQueryTest extends CommonClass
{
    public const DRIVER = 'mysql';
    protected const QUERY_REQUIRES_CONFLICTS   = false;
    protected const QUERY_WITH_VALUES          = 'INSERT INTO {table} ({email}, {name}) VALUES (?, ?) AS {target} ON DUPLICATE KEY UPDATE {email} = {target}.{email}, {name} = {target}.{name}';
    protected const QUERY_WITH_STATES_VALUES   = 'INSERT INTO {table} ({email}, {name}) VALUES (?, ?) AS {target} ON DUPLICATE KEY UPDATE {email} = {target}.{email}, {name} = {target}.{name}';
    protected const QUERY_WITH_MULTIPLE_ROWS   = 'INSERT INTO {table} ({email}, {name}) VALUES (?, ?), (?, ?) AS {target} ON DUPLICATE KEY UPDATE {email} = {target}.{email}, {name} = {target}.{name}';
    protected const QUERY_WITH_EXPRESSIONS     = 'INSERT INTO {table} ({email}, {name}, {created_at}, {updated_at}, {deleted_at}) VALUES (?, ?, NOW(), NOW(), ?) AS {target} ON DUPLICATE KEY UPDATE {email} = {target}.{email}, {name} = {target}.{name}, {created_at} = {target}.{created_at}, {updated_at} = {target}.{updated_at}, {deleted_at} = {target}.{deleted_at}';
    protected const QUERY_WITH_FRAGMENTS       = 'INSERT INTO {table} ({email}, {name}, {created_at}, {updated_at}, {deleted_at}) VALUES (?, ?, NOW(), datetime(\'now\'), ?) AS {target} ON DUPLICATE KEY UPDATE {email} = {target}.{email}, {name} = {target}.{name}, {created_at} = {target}.{created_at}, {updated_at} = {target}.{updated_at}, {deleted_at} = {target}.{deleted_at}';
    protected const QUERY_WITH_CUSTOM_FRAGMENT = 'INSERT INTO {table} ({email}, {name}, {expired_at}) VALUES (?, ?, NOW()) AS {target} ON DUPLICATE KEY UPDATE {email} = {target}.{email}, {name} = {target}.{name}, {expired_at} = {target}.{expired_at}';
}
