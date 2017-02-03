<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Pagination;

/**
 * Paginator with dependency on count of items.
 */
interface CountingInterface extends PaginatorInterface
{
    /**
     * Get instance of paginator with a given count. Must not affect existed paginator.
     *
     * @param int $count
     *
     * @return self|$this
     */
    public function withCount(int $count): self;
}