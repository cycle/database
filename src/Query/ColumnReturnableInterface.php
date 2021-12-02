<?php

declare(strict_types=1);

namespace Cycle\Database\Query;

interface ColumnReturnableInterface
{
    /**
     * Set returning column. If not set, the driver will detect PK automatically.
     */
    public function returningColumns(string ...$columns): self;
}
