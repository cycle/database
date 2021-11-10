<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Exception;

use Spiral\Database\Exception\StatementExceptionInterface as SpiralStatementExceptionInterface;

interface StatementExceptionInterface
{
    /**
     * Get query SQL.
     *
     * @return string
     */
    public function getQuery(): string;
}
\class_alias(StatementExceptionInterface::class, SpiralStatementExceptionInterface::class, false);
