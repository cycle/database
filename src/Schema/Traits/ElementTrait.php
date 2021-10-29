<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Schema\Traits;

use Cycle\Database\Driver\DriverInterface;
use Spiral\Database\Driver\DriverInterface as SpiralDriverInterface;

interface_exists(SpiralDriverInterface::class);

trait ElementTrait
{
    /** @var string */
    protected $name = '';

    /**  @var string */
    protected $table = '';

    /**
     * Associated table name (full name).
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Set element name.
     *
     * @param string $name
     * @return self|$this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get element name (unquoted).
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Element creation/definition syntax (specific to parent driver).
     *
     * @param DriverInterface $driver
     * @return string
     */
    abstract public function sqlStatement(SpiralDriverInterface $driver): string;
}
