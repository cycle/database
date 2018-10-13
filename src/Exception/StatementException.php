<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Exception;

/**
 * Query specific exception (bad parameters, database failure).
 */
class StatementException extends DatabaseException implements StatementExceptionInterface
{
    /**
     * @var string
     */
    private $query;

    /**
     * {@inheritdoc}
     *
     * @param \PDOException $exception
     */
    public function __construct(\PDOException $exception, string $query)
    {
        parent::__construct($exception->getMessage(), (int)$exception->getCode(), $exception);
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return \PDOException
     */
    public function pdoException(): \PDOException
    {
        /**
         * @var \PDOException $previous
         */
        $previous = $this->getPrevious();

        return $previous;
    }
}
