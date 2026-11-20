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

class AbstractDriverTest
{
    use m\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private Driver $driver;

    #[\Testo\Test]
    public function testLoggerShouldBeSet(): void
    {
        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('debug')
            ->once()->with('Insert ID: 0');

        $this->driver->setLogger($logger);
        $this->driver->lastInsertID();
    }

    #[\Testo\Test]
    public function testGetNotSetNameShouldThrowAnException(): void
    {
        \Testo\Expect::exception(\RuntimeException::class)->withMessageContaining('Driver name is not defined.');
        $this->driver->getName();
    }

    #[\Testo\Test]
    public function testWithName(): void
    {
        $handler = m::mock(HandlerInterface::class);
        $builder = m::mock(BuilderInterface::class);

        $handler->shouldReceive('withDriver')->once();
        $builder->shouldReceive('withDriver')->once();

        $driver = TestDriver::createWith(
            new SQLiteDriverConfig(),
            $handler,
            $builder,
        );

        $driver->getSchemaHandler()->shouldReceive('withDriver')->once();
        $driver->getQueryBuilder()->shouldReceive('withDriver')->once();

        $newDriver = $driver->withName('test');
        \Testo\Assert::same($newDriver->getName(), 'test');

        $this->checkImmutability($driver, $newDriver);
    }

    #[\Testo\Test]
    public function testClone(): void
    {
        $handler = m::mock(HandlerInterface::class);
        $builder = m::mock(BuilderInterface::class);

        $handler->shouldReceive('withDriver')->once();
        $builder->shouldReceive('withDriver')->once();

        $driver = TestDriver::createWith(
            new SQLiteDriverConfig(),
            $handler,
            $builder,
        );

        $driver->getSchemaHandler()->shouldReceive('withDriver')->once();
        $driver->getQueryBuilder()->shouldReceive('withDriver')->once();

        $newDriver = clone $driver;

        $this->checkImmutability($driver, $newDriver);
    }

    #[\Testo\Test]
    public function testLogsWithDisabledInterpolation(): void
    {
        $pdo = \Mockery::mock(PDOInterface::class)->shouldIgnoreMissing();
        $pdo->shouldReceive('prepare')->once()->andReturn(\Mockery::mock(PDOStatementInterface::class)->shouldIgnoreMissing());

        $driver = TestDriver::createWith(
            new SQLiteDriverConfig(),
            \Mockery::mock(HandlerInterface::class)->shouldIgnoreMissing(),
            \Mockery::mock(BuilderInterface::class)->shouldIgnoreMissing(),
            $pdo,
        );

        $logger = \Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $logger->shouldReceive('info')->once()->with('SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC', \Mockery::andAnyOtherArgs());
        $driver->setLogger($logger);

        $driver->query(
            'SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC',
            [1, 2, 3],
        );
    }

    #[\Testo\Test]
    public function testLogsWithEnabledInterpolation(): void
    {
        $pdo = \Mockery::mock(PDOInterface::class)->shouldIgnoreMissing();
        $pdo->shouldReceive('prepare')->once()->andReturn(\Mockery::mock(PDOStatementInterface::class)->shouldIgnoreMissing());

        $driver = TestDriver::createWith(
            new SQLiteDriverConfig(options: ['logInterpolatedQueries' => true]),
            \Mockery::mock(HandlerInterface::class)->shouldIgnoreMissing(),
            \Mockery::mock(BuilderInterface::class)->shouldIgnoreMissing(),
            $pdo,
        );

        $logger = \Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $logger->shouldReceive('info')->once()->with('SELECT * FROM sample_table WHERE id IN (1, 2, 3) ORDER BY id ASC', \Mockery::andAnyOtherArgs());
        $driver->setLogger($logger);

        $driver->query(
            'SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC',
            [1, 2, 3],
        );
    }

    #[\Testo\Test]
    public function testLogsQueryParameters(): void
    {
        $pdo = \Mockery::mock(PDOInterface::class)->shouldIgnoreMissing();
        $pdo->shouldReceive('prepare')->once()->andReturn(\Mockery::mock(PDOStatementInterface::class)->shouldIgnoreMissing());

        $driver = TestDriver::createWith(
            new SQLiteDriverConfig(options: [
                'logInterpolatedQueries' => false,
                'logQueryParameters' => true,
            ]),
            \Mockery::mock(HandlerInterface::class)->shouldIgnoreMissing(),
            \Mockery::mock(BuilderInterface::class)->shouldIgnoreMissing(),
            $pdo,
        );

        $logger = \Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $logger->shouldReceive('info')->once()->with('SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC', \Mockery::on(static function (array $context) {
            if (!isset($context['parameters'])) {
                return false;
            }

            $parametersAsString = \array_map('strval', $context['parameters']);

            $expectedParameters = ['1', '2', '3'];
            if ($parametersAsString !== $expectedParameters) {
                return false;
            }

            return isset($context['driver']);
        }), \Mockery::andAnyOtherArgs());
        $driver->setLogger($logger);

        $driver->query(
            'SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC',
            [1, 2, 3],
        );
    }

    #[\Testo\Test]
    public function testLogsWithEnabledInterpolationAndWithDatetimeMicroseconds(): void
    {
        $pdo = \Mockery::mock(PDOInterface::class)->shouldIgnoreMissing();
        $pdo->shouldReceive('prepare')->once()->andReturn(\Mockery::mock(PDOStatementInterface::class)->shouldIgnoreMissing());

        $driver = TestDriver::createWith(
            new SQLiteDriverConfig(options: ['logInterpolatedQueries' => true, 'withDatetimeMicroseconds' => true]),
            \Mockery::mock(HandlerInterface::class)->shouldIgnoreMissing(),
            \Mockery::mock(BuilderInterface::class)->shouldIgnoreMissing(),
            $pdo,
        );

        $date = new \DateTime('now');
        $logger = \Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $logger->shouldReceive('info')->once()->with(\sprintf(
            'SELECT * FROM sample_table WHERE name = \'%s\' AND registered > \'%s\'',
            'John Doe',
            $date->format('Y-m-d H:i:s.u'),
        ), \Mockery::andAnyOtherArgs());
        $driver->setLogger($logger);

        $driver->query('SELECT * FROM sample_table WHERE name = ? AND registered > ?', ['John Doe', $date]);
    }

    #[\Testo\Test]
    public function testUseCacheFromConfig(): void
    {
        $ref = new \ReflectionProperty(Driver::class, 'useCache');
        $ref->setAccessible(true);

        \Testo\Assert::true($ref->getValue(TestDriver::create(new SQLiteDriverConfig(queryCache: true))));
        \Testo\Assert::false($ref->getValue(TestDriver::create(new SQLiteDriverConfig(queryCache: false))));
    }

    #[\Testo\Test]
    public function testWithoutCache(): void
    {
        $ref = new \ReflectionProperty(Driver::class, 'useCache');
        $ref->setAccessible(true);

        $driver = TestDriver::create(new SQLiteDriverConfig(queryCache: true));

        \Testo\Assert::true($ref->getValue($driver));
        \Testo\Assert::false($ref->getValue($driver->withoutCache()));
    }

    #[\Testo\Test]
    public function testWithoutCacheTwice(): void
    {
        $driver = TestDriver::create(new SQLiteDriverConfig(queryCache: true));

        $ncDriver = $driver->withoutCache();

        \Testo\Assert::same($ncDriver->withoutCache(), $ncDriver);
    }

    #[\Testo\Test]
    public function testWithoutCacheOnWithoutCacheInitially(): void
    {
        $driver = TestDriver::create(new SQLiteDriverConfig(queryCache: false));

        \Testo\Assert::same($driver->withoutCache(), $driver);
    }

    #[\Testo\Test]
    public function testPdoNotClonedAfterCacheDisabled(): void
    {
        $ref = new \ReflectionMethod(Driver::class, 'getPDO');
        $ref->setAccessible(true);

        $driver = TestDriver::create(new SQLiteDriverConfig(queryCache: true));
        $oldPDO = $ref->invoke($driver);

        $driver = $driver->withoutCache();
        $newPDO = $ref->invoke($driver);

        \Testo\Assert::same($newPDO, $oldPDO);
    }

    #[\Testo\Lifecycle\BeforeTest]
    protected function setUp(): void
    {
        $this->driver = TestDriver::create(new SQLiteDriverConfig());
    }

    private function checkImmutability(DriverInterface $driver, DriverInterface $newDriver): void
    {
        // Immutability
        \Testo\Assert::notSame($newDriver, $driver);
        \Testo\Assert::notSame($newDriver->getSchemaHandler(), $driver->getSchemaHandler());
        \Testo\Assert::notSame($newDriver->getQueryBuilder(), $driver->getQueryBuilder());
    }
}
