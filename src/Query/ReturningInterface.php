<?php

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\FragmentInterface;

interface ReturningInterface extends QueryInterface
{
    /**
     * Set returning column or expression.
     *
     * If set multiple columns and the driver supports it, then an insert result will be an array of values.
     * If set one column and the driver supports it, then an insert result will be a single value,
     * not an array of values.
     *
     * If set multiple columns and the driver does not support it, an exception will be thrown.
     *
     * @throws BuilderException
     */
    public function returning(string|FragmentInterface ...$columns): self;
}
