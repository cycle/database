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
 * Query specific exception (bad parameters, database failure).
 */
class StatementException extends DatabaseException implements StatementExceptionInterface
{
    /** @var string */
    private $query;

    public function __construct(\Throwable $previous, string $query)
    {
        parent::__construct($previous->getMessage(), (int) $previous->getCode(), $previous);
        $this->query = $query;
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}
