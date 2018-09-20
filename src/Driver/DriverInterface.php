<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Driver;

interface DriverInterface
{
    /**
     * Driver specific database/table identifier quotation.
     *
     * @param string $identifier
     * @return string
     */
    public function identifier(string $identifier): string;

    /**
     * Check if table exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasTable(string $name): bool;

    /**
     * Clean (truncate) specified driver table.
     *
     * @param string $table Table name with prefix included.
     */
    public function truncateData(string $table);

    /**
     * Get every available table name as array.
     *
     * @return array
     */
    public function tableNames(): array;
}