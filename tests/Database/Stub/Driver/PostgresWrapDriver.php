<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Stub\Driver;

use Cycle\Database\Driver\Postgres\PostgresDriver;

class PostgresWrapDriver extends PostgresDriver
{
    use TestDriverTrait;
}
