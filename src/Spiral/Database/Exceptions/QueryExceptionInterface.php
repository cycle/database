<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Exceptions;

interface QueryExceptionInterface
{
    /**
     * Get query SQL.
     *
     * @return string
     */
    public function getQuery(): string;
}