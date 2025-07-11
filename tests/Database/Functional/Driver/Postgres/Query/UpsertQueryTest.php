<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

// phpcs:ignore
use Cycle\Database\Driver\Postgres\Query\PostgresUpsertQuery;
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpsertQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class UpsertQueryTest extends CommonClass
{
    public const DRIVER = 'postgres';
    protected const QUERY_INSTANCE = PostgresUpsertQuery::class;
}
