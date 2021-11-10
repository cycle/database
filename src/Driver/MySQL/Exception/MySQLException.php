<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL\Exception;

use Cycle\Database\Exception\DriverException;
use Spiral\Database\Driver\MySQL\Exception\MySQLException as SpiralMySQLException;

class MySQLException extends DriverException
{
}
\class_alias(MySQLException::class, SpiralMySQLException::class, false);
