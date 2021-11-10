<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Exception;

use Spiral\Database\Exception\SchemaException as SpiralSchemaException;

/**
 * Error while building table schema.
 */
class SchemaException extends DBALException
{
}
\class_alias(SchemaException::class, SpiralSchemaException::class, false);
