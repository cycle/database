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
use Spiral\Database\Exception\StatementException\ConstrainException as SpiralConstrainException;

class ConstrainException extends StatementException
{
}
\class_alias(ConstrainException::class, SpiralConstrainException::class, false);
