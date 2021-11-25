<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\SQLite\SQLiteCompiler;
use Cycle\Database\Driver\SQLite\SQLiteHandler;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\LoggerFactoryInterface;
use Cycle\Database\Query\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Throwable;

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
        $manager = new DatabaseManager(
            new DatabaseConfig([
                'connections' => [
                    'test' => new SQLiteDriverConfig(
                        driver: DatabaseManagerTestDriver::class
                    ),
                ],
            ])
        );

        $this->assertInstanceOf(DatabaseManagerTestDriver::class, $driver = $manager->driver('test'));

        $refl = new \ReflectionClass($driver);
        $property = $refl->getProperty('logger');
        $property->setAccessible(true);
        $this->assertNull($property->getValue($driver));
    }

    public function testDatabaseManagerWithLoggerAndWithoutLoggerFactoryShouldReturnLogger()
    {
        $manager = new DatabaseManager(
            new DatabaseConfig([
                'connections' => [
                    'test' => new SQLiteDriverConfig(
                        driver: DatabaseManagerTestDriver::class
                    ),
                ],
            ])
        );

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
            new DatabaseConfig([
                'connections' => [
                    'test' => new SQLiteDriverConfig(
                        driver: DatabaseManagerTestDriver::class
                    ),
                ],
            ]),
            $this->loggerFactory
        );

        $loggerFromFactory = $this->createMock(LoggerInterface::class);

        $this->loggerFactory->expects($this->once())
            ->method('getLogger')
            ->with($this->isInstanceOf(DatabaseManagerTestDriver::class))
            ->willReturn($loggerFromFactory);

        $manager->setLogger($this->logger);
        $driver = $manager->driver('test');

        $refl = new \ReflectionClass($driver);
        $property = $refl->getProperty('logger');
        $property->setAccessible(true);
        $this->assertSame($loggerFromFactory, $property->getValue($driver));
    }
}

class DatabaseManagerTestDriver extends Driver
{

    protected function mapException(Throwable $exception, string $query): StatementException
    {
        // TODO: Implement mapException() method.
    }

    public function getType(): string
    {
        return 'test';
    }

    public static function create(DriverConfig $config): DriverInterface
    {
        return new self(
            $config,
            new SQLiteHandler(),
            new SQLiteCompiler('""'),
            QueryBuilder::defaultBuilder()
        );
    }
}
