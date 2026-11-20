<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLServer\Driver;

use Cycle\Database\Driver\SQLServer\SQLServerDriver;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Tests\Feature\Common\Driver\DriverTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[OnlyDriver(Driver::SQLServer)]
final class DriverTest extends CommonClass
{
    #[Test]
    public function testCreateDoesNotReachTheServer(): void
    {
        $config = $this->driver->config($this->context->pool->env);

        $connection = clone $config->connection;
        $connection->password = 'definitely not the password';
        $config->connection = $connection;

        $driver = SQLServerDriver::create($config);

        Assert::false($driver->isConnected());

        // The failure arrives with the first statement, mapped like a failed connection on any driver.
        Expect::exception(StatementException::class);

        $driver->query('SELECT 1');
    }
}
