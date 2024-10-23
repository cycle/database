<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Exception;

/**
 * Schema sync related exception.
 */
class HandlerException extends DriverException implements StatementExceptionInterface
{
    public function __construct(StatementException $e)
    {
        parent::__construct($e->getMessage(), (int) $e->getCode(), $e);
    }

    public function getQuery(): string
    {
        return $this->getPrevious()->getQuery();
    }
}
