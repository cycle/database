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
    /**
     * @psalm-param non-empty-string $name
     *
     * @psalm-param non-empty-string $driver
     * @psalm-param non-empty-string|null $readDriver
     */
    public function __construct(
        private string $name,
        private string $prefix,
        private string $driver,
        private ?string $readDriver = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getReadDriver(): ?string
    {
        return $this->readDriver;
    }
}
