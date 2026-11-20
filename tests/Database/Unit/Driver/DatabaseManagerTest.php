<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\LoggerFactoryInterface;
use Cycle\Database\Tests\Unit\Stub\TestDriver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DatabaseManagerTest
{
    private LoggerFactoryInterface|MockObject $loggerFactory;
    private LoggerInterface|MockObject $logger;

    #[\Testo\Test]
    public function testDriverShouldHaveNameIfITHasNamedInterface(): void
    {
        $manager = new DatabaseManager(
            new DatabaseConfig([
                'connections' => [
                    'driver_without_name' => new SQLiteDriverConfig(
                        driver: TestDriver::class,
                    ),
                    'driver_with_name' => new SQLiteDriverConfig(),
                ],
            ]),
        );

        \Testo\Assert::same($manager->driver('driver_with_name')->getName(), 'driver_with_name');
    }

    #[\Testo\Test]
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

    #[\Testo\Test]
    public function testDatabaseManagerWithoutLoggerAndLoggerFactoryShouldReturnNullLogger(): void
    {
        $manager = new DatabaseManager($this->getDatabaseConfig());

        \Testo\Assert::instanceOf($driver = $manager->driver('test'), TestDriver::class);

        $refl = new \ReflectionClass($driver);
        $property = $refl->getProperty('logger');
        $property->setAccessible(true);
        \Testo\Assert::null($property->getValue($driver));
    }

    #[\Testo\Test]
    public function testDatabaseManagerWithLoggerAndWithoutLoggerFactoryShouldReturnLogger(): void
    {
        $manager = new DatabaseManager($this->getDatabaseConfig());

        $manager->setLogger($this->logger);
        $driver = $manager->driver('test');

        $refl = new \ReflectionClass($driver);
        $property = $refl->getProperty('logger');
        $property->setAccessible(true);
        \Testo\Assert::same($property->getValue($driver), $this->logger);
    }

    #[\Testo\Test]
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
        \Testo\Assert::same($property->getValue($driver), $this->logger);
    }

    #[\Testo\Test]
    public function testDatabaseManagerWithoutLoggerAndWithLoggerFactoryShouldReturnLoggerFromFactory(): void
    {
        $manager = new DatabaseManager(
            $this->getDatabaseConfig(),
            $this->loggerFactory,
        );

        $loggerFromFactory = \Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

        $this->loggerFactory->shouldReceive('getLogger')->once()->with(\Mockery::type(TestDriver::class), \Mockery::andAnyOtherArgs())->andReturn($loggerFromFactory);

        $driver = $manager->driver('test');

        $refl = new \ReflectionClass($driver);
        $property = $refl->getProperty('logger');
        $property->setAccessible(true);
        \Testo\Assert::same($property->getValue($driver), $loggerFromFactory);
    }

    #[\Testo\Lifecycle\BeforeTest]
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
                    driver: TestDriver::class,
                ),
            ],
        ]);
    }
}
