<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database;

/**
 * Represents single table index associated with set of columns.
 */
interface IndexInterface
{
    /**
     * Get element name (unquoted).
     *
     */
    public function getName(): string;

    /**
     * Check if index is unique.
     *
     */
    public function isUnique(): bool;

    /**
     * Column names used to form index.
     *
     */
    public function getColumns(): array;

    /**
     * Columns mapping to sorting order
     *
     */
    public function getSort(): array;
}
