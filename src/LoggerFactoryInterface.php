<?php

declare(strict_types=1);

namespace Cycle\Database;

use Cycle\Database\Driver\DriverInterface;
use Psr\Log\LoggerInterface;

interface LoggerFactoryInterface
{
    public function getLogger(?DriverInterface $driver = null): LoggerInterface;
}
