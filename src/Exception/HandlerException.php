<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Exception;

/**
 * Schema sync related exception.
 */
class HandlerException extends DriverException implements StatementExceptionInterface
{
    /**
     * @param StatementException $e
     */
    public function __construct(StatementException $e)
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
