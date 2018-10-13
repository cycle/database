<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

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