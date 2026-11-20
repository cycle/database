<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\TransactionsTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
class TransactionsTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
