<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Exception;

use Spiral\Database\Exception\DatabaseException as SpiralDatabaseException;

/**
 * Generic database exception.
 */
class DatabaseException extends DBALException
{
}
\class_alias(DatabaseException::class, SpiralDatabaseException::class, false);
