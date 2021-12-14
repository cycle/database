<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Schema\Traits;

use Cycle\Database\Driver\Driver;

trait ElementTrait
{
    /**
     * Associated table name (full name).
     *
     * @psalm-return non-empty-string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Set element name.
     *
     * @psalm-param non-empty-string $name
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get element name (unquoted).
     *
     * @psalm-return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Element creation/definition syntax (specific to parent driver).
     */
    abstract public function sqlStatement(Driver $driver): string;
}
