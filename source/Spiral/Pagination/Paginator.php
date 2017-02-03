<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Pagination;

/**
 * Simple predictable paginator.
 */
class Paginator implements PagedInterface, \Countable
{
    /**
     * @var int
     */
    private $pageNumber = 1;

    /**
     * @var int
     */
    private $countPages = 1;

    /**
     * Pagination limit.
     *
     * @var int
     */
    private $limit = 25;

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @param int $limit
     * @param int $count
     */
    public function __construct(int $limit = 25, int $count = 0)
    {
        $this->limit = $limit;
        $this->count = $count;
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     */
    public function withLimit(int $limit): PagedInterface
    {
        $paginator = clone $this;
        $paginator->limit = $limit;

        return $paginator;
    }

    /**
     * {@inheritdoc}
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * {@inheritdoc}
     */
    public function withPage(int $number): PagedInterface
    {
        $paginator = clone $this;
        $paginator->pageNumber = max($number, 0);

        //Real page number
        return $paginator;
    }

    /**
     * {@inheritdoc}
     */
    public function getPage(): int
    {
        if ($this->pageNumber < 1) {
            return 1;
        }

        if ($this->pageNumber > $this->countPages) {
            return $this->countPages;
        }

        return $this->pageNumber;
    }

    /**
     * {@inheritdoc}
     */
    public function getOffset(): int
    {
        return ($this->getPage() - 1) * $this->limit;
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     */
    public function withCount(int $count): CountingInterface
    {
        $paginator = clone $this;

        return $paginator->setCount($count);
    }

    /**
     * Alias for count.
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    public function countPages(): int
    {
        return $this->countPages;
    }

    /**
     * {@inheritdoc}
     */
    public function countDisplayed(): int
    {
        if ($this->getPage() == $this->countPages) {
            return $this->count - $this->getOffset();
        }

        return $this->limit;
    }

    /**
     * {@inheritdoc}
     */
    public function isRequired(): bool
    {
        return ($this->countPages > 1);
    }

    /**
     * {@inheritdoc}
     */
    public function nextPage()
    {
        if ($this->getPage() != $this->countPages) {
            return $this->getPage() + 1;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function previousPage()
    {
        if ($this->getPage() > 1) {
            return $this->getPage() - 1;
        }

        return null;
    }

    /**
     * Non-Immutable version of withCount.
     *
     * @param int $count
     *
     * @return self|$this
     */
    private function setCount(int $count): self
    {
        $this->count = max($count, 0);
        if ($this->count > 0) {
            $this->countPages = (int)ceil($this->count / $this->limit);
        } else {
            $this->countPages = 1;
        }

        return $this;
    }
}