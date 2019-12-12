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
 * Query specific exception (bad parameters, database failure).
 */
class StatementException extends DatabaseException implements StatementExceptionInterface
{
    /** @var string */
    private $query;

    /**
     * {@inheritdoc}
     *
     * @param \Throwable $previous
     */
    public function __construct(\Throwable $previous, string $query)
    {
        parent::__construct($previous->getMessage(), (int)$previous->getCode(), $previous);
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}
