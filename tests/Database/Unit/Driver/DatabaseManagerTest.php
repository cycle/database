<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\LoggerFactoryInterface;
use Cycle\Testing\Database\Mock\StubDriver;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class DatabaseManagerTest
{
    private LoggerFactoryInterface|MockInterface $loggerFactory;
    private LoggerInterface|MockInterface $logger;

    public function testDriverShouldHaveNameIfITHasNamedInterface(): void
    {
        $manager = new DatabaseManager(
            new DatabaseConfig([
                'connections' => [
                    'driver_without_name' => new SQLiteDriverConfig(
                        driver: StubDriver::class,
                    ),
                    'driver_with_name' => new SQLiteDriverConfig(),
                ],
            ]),
        );

        Assert::same($manager->driver('driver_with_name')->getName(), 'driver_with_name');
    }

    public function testSetsLoggerShouldPassLoggerToDrivers(): void
    {
        $manager = new DatabaseManager(
            new DatabaseConfig([]),
            $this->loggerFactory,
        );
        $manager->addDriver(
            'driver1',
            $driver1 = \Mockery::mock(Driver::class)->shouldIgnoreMissing(),
        );

        $driver1->shouldReceive('setLogger')->once()->with($this->logger, \Mockery::andAnyOtherArgs());

        $manager->addDriver(
            'driver2',
            $driver2 = \Mockery::mock(Driver::class)->shouldIgnoreMissing(),
        );
        $driver2->shouldReceive('setLogger')->once()->with($this->logger, \Mockery::andAnyOtherArgs());

        $manager->addDriver(
            'driverWithoutLogger',
            $driver3 = \Mockery::mock(DriverInterface::class)->shouldIgnoreMissing(),
        );

        $manager->setLogger($this->logger);
    }

    public function testDatabaseManagerWithoutLoggerAndLoggerFactoryShouldReturnNullLogger(): void
    {
        $manager = new DatabaseManager($this->getDatabaseConfig());

        Assert::instanceOf($driver = $manager->driver('test'), StubDriver::class);

        $refl = new \ReflectionClass($driver);
        $property = $refl->getProperty('logger');
        $property->setAccessible(true);
        Assert::null($property->getValue($driver));
    }

    public function testDatabaseManagerWithLoggerAndWithoutLoggerFactoryShouldReturnLogger(): void
    {
        $manager = new DatabaseManager($this->getDatabaseConfig());

        $manager->setLogger($this->logger);
        $driver = $manager->driver('test');

        $refl = new \ReflectionClass($driver);
        $property = $refl->getProperty('logger');
        $property->setAccessible(true);
        Assert::same($property->getValue($driver), $this->logger);
    }

    public function testDatabaseManagerWithLoggerAndWithLoggerFactoryShouldReturnLogger(): void
    {
        $manager = new DatabaseManager(
            $this->getDatabaseConfig(),
            $this->loggerFactory,
        );

        $this->loggerFactory->shouldReceive('getLogger')->never();

        $manager->setLogger($this->logger);
        $driver = $manager->driver('test');

        $refl = new \ReflectionClass($driver);
        $property = $refl->getProperty('logger');
        $property->setAccessible(true);
        Assert::same($property->getValue($driver), $this->logger);
    }

    public function testDatabaseManagerWithoutLoggerAndWithLoggerFactoryShouldReturnLoggerFromFactory(): void
    {
        $manager = new DatabaseManager(
            $this->getDatabaseConfig(),
            $this->loggerFactory,
        );

        $loggerFromFactory = \Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

        $this->loggerFactory->shouldReceive('getLogger')->once()->with(\Mockery::type(StubDriver::class), \Mockery::andAnyOtherArgs())->andReturn($loggerFromFactory);

        $driver = $manager->driver('test');

        $refl = new \ReflectionClass($driver);
        $property = $refl->getProperty('logger');
        $property->setAccessible(true);
        Assert::same($property->getValue($driver), $loggerFromFactory);
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->loggerFactory = \Mockery::mock(LoggerFactoryInterface::class)->shouldIgnoreMissing();
        $this->logger = \Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
    }

    private function getDatabaseConfig(): DatabaseConfig
    {
        return new DatabaseConfig([
            'connections' => [
                'test' => new SQLiteDriverConfig(
                    driver: StubDriver::class,
                ),
            ],
        ]);
    }
}
