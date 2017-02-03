<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Pagination;

/**
 * Paginator with predictable length (count).
 */
interface PagedInterface extends CountingInterface
{
    /**
     * Set pagination limit. Immutable.
     *
     * @param int $limit
     *
     * @return PagedInterface|$this
     */
    public function withLimit(int $limit): self;

    /**
     * Get pagination limit.
     *
     * @return int
     */
    public function getLimit(): int;

    /**
     * Set page number.
     *
     * @param int $number
     *
     * @return PagedInterface|$this
     */
    public function withPage(int $number): self;

    /**
     * Get current page number.
     *
     * @return int
     */
    public function getPage(): int;

    /**
     * The count of pages required to represent all records using a specified limit value.
     *
     * @return int
     */
    public function countPages(): int;

    /**
     * The count or records displayed on current page can vary from 0 to any limit value. Only the
     * last page can have less records than is specified in the limit.
     *
     * @return int
     */
    public function countDisplayed(): int;

    /**
     * Does paginator needed to be applied? Should return false if all records can be shown on one
     * page.
     *
     * @return bool
     */
    public function isRequired(): bool;

    /**
     * Next page number. Should return will be false if the current page is the last page.
     *
     * @return null|int
     */
    public function nextPage();

    /**
     * Previous page number. Should return false if the current page is first page.
     *
     * @return null|int
     */
    public function previousPage();
}
