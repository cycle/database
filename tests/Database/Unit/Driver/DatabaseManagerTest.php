<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\LoggerFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DatabaseManagerTest extends TestCase
{
    private LoggerFactoryInterface|MockObject $loggerFactory;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerFactory = $this->createMock(LoggerFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testSetsLoggerShouldPassLoggerToDrivers()
    {
        $manager = new DatabaseManager(
            new DatabaseConfig([]),
            $this->loggerFactory
        );
        $manager->addDriver(
            'driver1',
            $driver1 = $this->createMock(Driver::class)
        );

        $driver1->expects($this->once())->method('setLogger')->with($this->logger);

        $manager->addDriver(
            'driver2',
            $driver2 = $this->createMock(Driver::class)
        );
        $driver2->expects($this->once())->method('setLogger')->with($this->logger);

        $manager->addDriver(
            'driverWithoutLogger',
            $driver3 = $this->createMock(DriverInterface::class)
        );

        $manager->setLogger($this->logger);
    }

    public function testDatabaseManagerWithoutLoggerAndLoggerFactoryShouldReturnNullLogger()
    {
        $manager = new DatabaseManager($this->getDatabaseConfig());

        $this->assertInstanceOf(TestDriver::class, $driver = $manager->driver('test'));

        $refl = new \ReflectionClass($driver);
        $property = $refl->getProperty('logger');
        $property->setAccessible(true);
        $this->assertNull($property->getValue($driver));
    }

    public function testDatabaseManagerWithLoggerAndWithoutLoggerFactoryShouldReturnLogger()
    {
        $manager = new DatabaseManager($this->getDatabaseConfig());

        $manager->setLogger($this->logger);
        $driver = $manager->driver('test');

        $refl = new \ReflectionClass($driver);
        $property = $refl->getProperty('logger');
        $property->setAccessible(true);
        $this->assertSame($this->logger, $property->getValue($driver));
    }

    public function testDatabaseManagerWithLoggerAndWithLoggerFactoryShouldReturnLoggerFromFactory()
    {
        $manager = new DatabaseManager(
            $this->getDatabaseConfig(),
            $this->loggerFactory
        );

        $loggerFromFactory = $this->createMock(LoggerInterface::class);

        $this->loggerFactory->expects($this->once())
            ->method('getLogger')
            ->with($this->isInstanceOf(TestDriver::class))
            ->willReturn($loggerFromFactory);

        $manager->setLogger($this->logger);
        $driver = $manager->driver('test');

        $refl = new \ReflectionClass($driver);
        $property = $refl->getProperty('logger');
        $property->setAccessible(true);
        $this->assertSame($loggerFromFactory, $property->getValue($driver));
    }

    /**
     * @return DatabaseConfig
     */
    private function getDatabaseConfig(): DatabaseConfig
    {
        return new DatabaseConfig([
            'connections' => [
                'test' => new SQLiteDriverConfig(
                    driver: TestDriver::class
                ),
            ],
        ]);
    }
}
