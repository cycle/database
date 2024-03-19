<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Driver\PDOInterface;
use Cycle\Database\Driver\PDOStatementInterface;
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
        $this->expectExceptionMessage('Driver name is not defined.');
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

    public function testLogsWithDisabledInterpolation(): void
    {
        $pdo = $this->createMock(PDOInterface::class);
        $pdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->createMock(PDOStatementInterface::class));

        $driver = TestDriver::createWith(
            new SQLiteDriverConfig(),
            $this->createMock(HandlerInterface::class),
            $this->createMock(BuilderInterface::class),
            $pdo
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC');
        $driver->setLogger($logger);

        $driver->query(
            'SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC',
            [1, 2, 3]
        );
    }

    public function testLogsWithEnabledInterpolation(): void
    {
        $pdo = $this->createMock(PDOInterface::class);
        $pdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->createMock(PDOStatementInterface::class));

        $driver = TestDriver::createWith(
            new SQLiteDriverConfig(options: ['logInterpolatedQueries' => true]),
            $this->createMock(HandlerInterface::class),
            $this->createMock(BuilderInterface::class),
            $pdo
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('SELECT * FROM sample_table WHERE id IN (1, 2, 3) ORDER BY id ASC');
        $driver->setLogger($logger);

        $driver->query(
            'SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC',
            [1, 2, 3]
        );
    }

    public function testLogsQueryParameters(): void
    {
        $pdo = $this->createMock(PDOInterface::class);
        $pdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->createMock(PDOStatementInterface::class));

        $driver = TestDriver::createWith(
            new SQLiteDriverConfig(options: [
                'logInterpolatedQueries' => false,
                'logQueryParameters' => true,
            ]),
            $this->createMock(HandlerInterface::class),
            $this->createMock(BuilderInterface::class),
            $pdo
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC'),
                $this->callback(function (array $context) {
                    if (!isset($context['parameters'])) {
                        return false;
                    }

                    $parametersAsString = array_map('strval', $context['parameters']);

                    $expectedParameters = ['1', '2', '3'];
                    if ($parametersAsString !== $expectedParameters) {
                        return false;
                    }

                    return true;
                })
            );
        $driver->setLogger($logger);

        $driver->query(
            'SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC',
            [1, 2, 3]
        );
    }

    public function testLogsWithEnabledInterpolationAndWithDatetimeMicroseconds(): void
    {
        $pdo = $this->createMock(PDOInterface::class);
        $pdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->createMock(PDOStatementInterface::class));

        $driver = TestDriver::createWith(
            new SQLiteDriverConfig(options: ['logInterpolatedQueries' => true, 'withDatetimeMicroseconds' => true]),
            $this->createMock(HandlerInterface::class),
            $this->createMock(BuilderInterface::class),
            $pdo
        );

        $date = new \DateTime('now');
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with(\sprintf(
                'SELECT * FROM sample_table WHERE name = \'%s\' AND registered > \'%s\'',
                'John Doe',
                $date->format('Y-m-d H:i:s.u')
            ));
        $driver->setLogger($logger);

        $driver->query('SELECT * FROM sample_table WHERE name = ? AND registered > ?', ['John Doe', $date]);
    }

    public function testUseCacheFromConfig(): void
    {
        $ref = new \ReflectionProperty(Driver::class, 'useCache');
        $ref->setAccessible(true);

        $this->assertTrue($ref->getValue(TestDriver::create(new SQLiteDriverConfig(queryCache: true))));
        $this->assertFalse($ref->getValue(TestDriver::create(new SQLiteDriverConfig(queryCache: false))));
    }

    public function testWithoutCache(): void
    {
        $ref = new \ReflectionProperty(Driver::class, 'useCache');
        $ref->setAccessible(true);

        $driver = TestDriver::create(new SQLiteDriverConfig(queryCache: true));

        $this->assertTrue($ref->getValue($driver));
        $this->assertFalse($ref->getValue($driver->withoutCache()));
    }

    public function testWithoutCacheTwice(): void
    {
        $driver = TestDriver::create(new SQLiteDriverConfig(queryCache: true));

        $ncDriver = $driver->withoutCache();

        $this->assertSame($ncDriver, $ncDriver->withoutCache());
    }

    public function testWithoutCacheOnWithoutCacheInitially(): void
    {
        $driver = TestDriver::create(new SQLiteDriverConfig(queryCache: false));

        $this->assertSame($driver, $driver->withoutCache());
    }

    public function testPdoNotClonedAfterCacheDisabled(): void
    {
        $ref = new \ReflectionMethod(Driver::class, 'getPDO');
        $ref->setAccessible(true);

        $driver = TestDriver::create(new SQLiteDriverConfig(queryCache: true));
        $oldPDO = $ref->invoke($driver);

        $driver = $driver->withoutCache();
        $newPDO = $ref->invoke($driver);

        $this->assertSame($oldPDO, $newPDO);
    }

    private function checkImmutability(DriverInterface $driver, DriverInterface $newDriver): void
    {
        // Immutability
        $this->assertNotSame($driver, $newDriver);
        $this->assertNotSame($driver->getSchemaHandler(), $newDriver->getSchemaHandler());
        $this->assertNotSame($driver->getQueryBuilder(), $newDriver->getQueryBuilder());
    }
}
