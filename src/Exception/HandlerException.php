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

/**
 * Schema sync related exception.
 */
class HandlerException extends DriverException implements StatementExceptionInterface
{
    /**
     * @param SpiralStatementException|StatementException $e The signature of this
     *        argument will be changed to {@see StatementException} in future release.
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
