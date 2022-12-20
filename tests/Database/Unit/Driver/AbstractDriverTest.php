<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Query\BuilderInterface;
use Cycle\Database\Tests\Unit\Stub\TestDriver;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AbstractDriverTest extends TestCase
{
    use m\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = TestDriver::create(new SQLiteDriverConfig());
    }

    public function testLoggerShouldBeSet(): void
    {
        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('debug')
            ->once()->with('Insert ID: 0');

        $this->driver->setLogger($logger);
        $this->driver->lastInsertID();
    }

    public function testGetNotSetNameShouldThrowAnException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectErrorMessage('Driver name is not defined.');
        $this->driver->getName();
    }

    public function testWithName(): void
    {
        $handler = m::mock(HandlerInterface::class);
        $builder = m::mock(BuilderInterface::class);

        $handler->shouldReceive('withDriver')->once();
        $builder->shouldReceive('withDriver')->once();

        $driver = TestDriver::createWith(
            new SQLiteDriverConfig(),
            $handler,
            $builder
        );

        $driver->getSchemaHandler()->shouldReceive('withDriver')->once();
        $driver->getQueryBuilder()->shouldReceive('withDriver')->once();

        $newDriver = $driver->withName('test');
        $this->assertSame('test', $newDriver->getName());

        $this->checkImmutability($driver, $newDriver);
    }

    public function testClone(): void
    {
        $handler = m::mock(HandlerInterface::class);
        $builder = m::mock(BuilderInterface::class);

        $handler->shouldReceive('withDriver')->once();
        $builder->shouldReceive('withDriver')->once();

        $driver = TestDriver::createWith(
            new SQLiteDriverConfig(),
            $handler,
            $builder
        );

        $driver->getSchemaHandler()->shouldReceive('withDriver')->once();
        $driver->getQueryBuilder()->shouldReceive('withDriver')->once();

        $newDriver = clone $driver;

        $this->checkImmutability($driver, $newDriver);
    }

    private function checkImmutability(DriverInterface $driver, DriverInterface $newDriver): void
    {
        // Immutability
        $this->assertNotSame($driver, $newDriver);
        $this->assertNotSame($driver->getSchemaHandler(), $newDriver->getSchemaHandler());
        $this->assertNotSame($driver->getQueryBuilder(), $newDriver->getQueryBuilder());
    }
}
