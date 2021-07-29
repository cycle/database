<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Schema\Traits;

use Cycle\Database\Driver\Driver;

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
     * @param Driver $driver
     * @return string
     */
    abstract public function sqlStatement(Driver $driver): string;
}
