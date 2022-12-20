<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Stub\Driver;

use Cycle\Database\Driver\MySQL\MySQLDriver;

class MysqlWrapDriver extends MySQLDriver
{
    use TestDriverTrait;
}
