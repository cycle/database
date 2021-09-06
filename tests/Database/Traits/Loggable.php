<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Traits;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Tests\Utils\TestLogger;

trait Loggable
{
    /** @var TestLogger */
    public static $logger;

    protected function setUpLogger(DriverInterface $driver)
    {
        static::$logger = static::$logger ?? new TestLogger();
        $driver->setLogger(static::$logger);

        return $this;
    }

    protected function enableProfiling(): void
    {
        static::$logger->enable();
    }

    protected function disableProfiling(): void
    {
        static::$logger->disable();
    }
}
