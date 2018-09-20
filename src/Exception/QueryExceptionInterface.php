<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Exception;

interface QueryExceptionInterface
{
    /**
     * Get query SQL.
     *
     * @return string
     */
    public function getQuery(): string;
}