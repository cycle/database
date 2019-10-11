<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Database\Driver\Traits;

use Spiral\Logger\Traits\LoggerTrait;

trait ProfilingTrait
{
    use LoggerTrait;

    /** @var bool */
    private $profiling = false;

    /**
     * Enable or disable driver query profiling.
     *
     * @param bool $profiling Enable or disable driver profiling.
     */
    public function setProfiling(bool $profiling = true): void
    {
        $this->profiling = $profiling;
    }

    /**
     * Check if profiling mode is enabled.
     *
     * @return bool
     */
    public function isProfiling(): bool
    {
        return $this->profiling;
    }
}
