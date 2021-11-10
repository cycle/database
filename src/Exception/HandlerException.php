<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Exception;

use Spiral\Database\Exception\StatementException as SpiralStatementException;
use Spiral\Database\Exception\HandlerException as SpiralHandlerException;

class_exists(SpiralStatementException::class);

/**
 * Schema sync related exception.
 */
class HandlerException extends DriverException implements StatementExceptionInterface
{
    /**
     * @param StatementException $e
     */
    public function __construct(SpiralStatementException $e)
    {
        parent::__construct($e->getMessage(), $e->getCode(), $e);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): string
    {
        return $this->getPrevious()->getQuery();
    }
}
\class_alias(HandlerException::class, SpiralHandlerException::class, false);
