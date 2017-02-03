<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Pagination\Traits;

use Interop\Container\ContainerInterface;
use Spiral\Core\Exceptions\ScopeException;
use Spiral\Pagination\CountingInterface;
use Spiral\Pagination\Exceptions\PaginationException;
use Spiral\Pagination\PaginatorInterface;
use Spiral\Pagination\PaginatorsInterface;

/**
 * Provides ability to paginate associated instance. Will work with default Paginator or fetch one
 * from container.
 *
 * Compatible with PaginatorAwareInterface.
 */
trait PaginatorTrait
{
    /**
     * @internal
     *
     * @var PaginatorInterface|null
     */
    private $paginator;

    /**
     * Indication that object was paginated.
     *
     * @return bool
     */
    public function hasPaginator(): bool
    {
        return $this->paginator instanceof PaginatorInterface;
    }

    /**
     * Manually set paginator instance for specific object.
     *
     * @param PaginatorInterface $paginator
     *
     * @return $this
     */
    public function setPaginator(PaginatorInterface $paginator)
    {
        $this->paginator = $paginator;

        return $this;
    }

    /**
     * Get paginator for the current selection. Paginate method should be already called.
     *
     * @see hasPaginator()
     * @see paginate()
     *
     * @return PaginatorInterface
     */
    public function getPaginator(): PaginatorInterface
    {
        if (!$this->hasPaginator()) {
            throw new PaginationException("Unable to get paginator, no paginator were set");
        }

        return $this->paginator;
    }

    /**
     * Paginate current selection using Paginator class.
     *
     * @param int    $limit     Pagination limit.
     * @param string $parameter Name of parameter to associate paginator with, by default query
     *                          parameter of active request to be used.
     *
     * @return $this
     *
     * @throws ScopeException
     */
    public function paginate(int $limit = 25, string $parameter = 'page')
    {
        //We are required to fetch paginator from associated container or shared container
        $container = $this->iocContainer();

        if (empty($container) || !$container->has(PaginatorsInterface::class)) {
            throw new ScopeException(
                'Unable to create paginator, PaginatorsInterface binding is missing or container not set'
            );
        }

        /**
         * @var PaginatorsInterface $factory
         */
        $factory = $container->get(PaginatorsInterface::class);

        //Now we can create new instance of paginator using factory
        $this->paginator = $factory->createPaginator($parameter, $limit);

        return $this;
    }

    /**
     * Get paginator instance configured for a given count. Must not affect already associated
     * paginator instance.
     *
     * Attention: this method MUST be called from a child class in order to properly set paginator
     * counts IF paginator support it.
     *
     * @param int|null $count Can be skipped.
     *
     * @return PaginatorInterface
     */
    protected function configurePaginator(int $count = null): PaginatorInterface
    {
        $paginator = $this->getPaginator();

        if (!empty($count) && $paginator instanceof CountingInterface) {
            $paginator = $paginator->withCount($count);
        } else {
            $paginator = clone $paginator;
        }

        return $paginator;
    }

    /**
     * @return ContainerInterface
     */
    abstract protected function iocContainer();
}
