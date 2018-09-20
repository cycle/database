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
class HandlerException extends DriverException implements QueryExceptionInterface
{
    /** @var string */
    private $query;

    /**
     * @param QueryException $e
     */
    public function __construct(QueryException $e)
    {
        parent::__construct($e->getMessage(), $e->getCode(), $e);
        $this->query = $e->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}