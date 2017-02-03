<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Pagination;

/**
 * Generic paginator interface with ability to set/get page and limit values.
 */
interface PaginatorInterface
{
    /**
     * Get pagination limit value.
     *
     * @return int
     */
    public function getLimit(): int;

    /**
     * Get calculated offset value.
     *
     * @return int
     */
    public function getOffset(): int;
}