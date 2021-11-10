<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Exception;

use Spiral\Database\Exception\CompilerException as SpiralCompilerException;

/**
 * Error while compiling query based on builder options.
 */
class CompilerException extends DBALException
{
}
\class_alias(CompilerException::class, SpiralCompilerException::class, false);
