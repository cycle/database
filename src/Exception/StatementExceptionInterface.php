<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Exception;

interface StatementExceptionInterface
{
    /**
     * Get query SQL.
     *
     * @return string
     */
    public function getQuery(): string;
}