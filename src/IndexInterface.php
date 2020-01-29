<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database;

/**
 * Represents single table index associated with set of columns.
 */
interface IndexInterface
{
    /**
     * Get element name (unquoted).
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if index is unique.
     *
     * @return bool
     */
    public function isUnique(): bool;

    /**
     * Column names used to form index.
     *
     * @return array
     */
    public function getColumns(): array;

    /**
     * Columns mapping to sorting order
     *
     * @return array
     */
    public function getSort(): array;
}
