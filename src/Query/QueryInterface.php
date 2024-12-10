<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Injection\FragmentInterface;

interface QueryInterface extends FragmentInterface
{
    /**
     * Associate query with driver.
     *
     * @return $this
     */
    public function withDriver(DriverInterface $driver, ?string $prefix = null): self;

    public function getDriver(): ?DriverInterface;

    /**
     * Isolation prefix associated with the query.
     *
     */
    public function getPrefix(): ?string;
}
