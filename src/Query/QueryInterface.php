<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query;

use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Injection\FragmentInterface;

interface QueryInterface extends FragmentInterface
{
    /**
     * Associate query with driver.
     *
     * @param DriverInterface $driver
     * @param string|null     $prefix
     * @return $this
     */
    public function withDriver(DriverInterface $driver, string $prefix = null): self;

    /**
     * @return DriverInterface|null
     */
    public function getDriver(): ?DriverInterface;

    /**
     * Isolation prefix associated with the query.
     *
     * @return string|null
     */
    public function getPrefix(): ?string;
}
