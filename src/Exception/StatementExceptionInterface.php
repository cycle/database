<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Exception;

interface StatementExceptionInterface
{
    /**
     * Get query SQL.
     *
     * @return string
     */
    public function getQuery(): string;
}
