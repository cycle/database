<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config;

final class DatabasePartial
{
    /** @var string */
    private $name;

    /** @var string */
    private $prefix;

    /** @var string */
    private $driver;

    /** @var null|string */
    private $readDriver;

    /**
     * @param string      $name
     * @param string      $prefix
     * @param string      $driver
     * @param string|null $readDriver
     */
    public function __construct(
        string $name,
        string $prefix,
        string $driver,
        string $readDriver = null
    ) {
        $this->name = $name;
        $this->prefix = $prefix;
        $this->driver = $driver;
        $this->readDriver = $readDriver;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * @return null|string
     */
    public function getReadDriver(): ?string
    {
        return $this->readDriver;
    }
}
