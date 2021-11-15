<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\DeleteQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class DeleteQueryTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
