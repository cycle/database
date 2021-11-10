<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Exception\StatementException;

use Cycle\Database\Exception\StatementException;
use Spiral\Database\Exception\StatementException\ConnectionException as SpiralConnectionException;

/**
 * Connection issue while the query.
 */
class ConnectionException extends StatementException
{
}
\class_alias(ConnectionException::class, SpiralConnectionException::class, false);
