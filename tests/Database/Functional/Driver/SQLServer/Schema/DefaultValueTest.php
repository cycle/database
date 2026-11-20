<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\DefaultValueTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
class DefaultValueTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
