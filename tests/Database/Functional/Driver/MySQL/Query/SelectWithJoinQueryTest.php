<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\SelectWithJoinQueryTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class SelectWithJoinQueryTest extends CommonClass
{
    public const DRIVER = 'mysql';
}
