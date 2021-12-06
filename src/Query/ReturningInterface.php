<?php

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Injection\FragmentInterface;

interface ReturningInterface extends QueryInterface
{
    /**
     * Set returning column or expression.
     */
    public function returning(string|FragmentInterface ...$columns): self;
}
