<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query;

use Spiral\Database\Driver\CompilerInterface;
use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Injection\FragmentInterface;

interface BuilderInterface extends FragmentInterface
{
    /**
     * @return DriverInterface|null
     */
    public function getDriver(): ?DriverInterface;

    /**
     * @return CompilerInterface|null
     */
    public function getCompiler(): ?CompilerInterface;
}
