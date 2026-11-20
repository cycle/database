<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Config\ConnectionConfig;
use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Driver\PDOInterface;
use Cycle\Database\Driver\PDOStatementInterface;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\BuilderInterface;
use Cycle\Database\StatementInterface;
use Cycle\Database\Tests\Fixtures\FooBarEnum;
use Cycle\Testing\Database\Mock\StubDriver;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class AbstractDriverTest
{
    private Driver $driver;

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
        Expect::exception(\RuntimeException::class)->withMessageContaining('Driver name is not defined.');
        $this->driver->getName();
    }

    public function testWithName(): void
    {
        $handler = m::mock(HandlerInterface::class);
        $builder = m::mock(BuilderInterface::class);

        $handler->shouldReceive('withDriver')->once();
        $builder->shouldReceive('withDriver')->once();

        $driver = StubDriver::make(
            config: new SQLiteDriverConfig(),
            handler: $handler,
            builder: $builder,
        );

        $driver->getSchemaHandler()->shouldReceive('withDriver')->once();
        $driver->getQueryBuilder()->shouldReceive('withDriver')->once();

        $newDriver = $driver->withName('test');
        Assert::same($newDriver->getName(), 'test');

        $this->checkImmutability($driver, $newDriver);
    }

    public function testClone(): void
    {
        $handler = m::mock(HandlerInterface::class);
        $builder = m::mock(BuilderInterface::class);

        $handler->shouldReceive('withDriver')->once();
        $builder->shouldReceive('withDriver')->once();

        $driver = StubDriver::make(
            config: new SQLiteDriverConfig(),
            handler: $handler,
            builder: $builder,
        );

        $driver->getSchemaHandler()->shouldReceive('withDriver')->once();
        $driver->getQueryBuilder()->shouldReceive('withDriver')->once();

        $newDriver = clone $driver;

        $this->checkImmutability($driver, $newDriver);
    }

    public function testLogsWithDisabledInterpolation(): void
    {
        $pdo = m::mock(PDOInterface::class)->shouldIgnoreMissing();
        $pdo->shouldReceive('prepare')->once()->andReturn(m::mock(PDOStatementInterface::class)->shouldIgnoreMissing());

        $driver = StubDriver::make(
            config: new SQLiteDriverConfig(),
            handler: m::mock(HandlerInterface::class)->shouldIgnoreMissing(),
            builder: m::mock(BuilderInterface::class)->shouldIgnoreMissing(),
            pdo: $pdo,
        );

        $logger = m::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $logger->shouldReceive('info')->once()->with('SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC', m::andAnyOtherArgs());
        $driver->setLogger($logger);

        $driver->query(
            'SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC',
            [1, 2, 3],
        );
    }

    public function testLogsWithEnabledInterpolation(): void
    {
        $pdo = m::mock(PDOInterface::class)->shouldIgnoreMissing();
        $pdo->shouldReceive('prepare')->once()->andReturn(m::mock(PDOStatementInterface::class)->shouldIgnoreMissing());

        $driver = StubDriver::make(
            config: new SQLiteDriverConfig(options: ['logInterpolatedQueries' => true]),
            handler: m::mock(HandlerInterface::class)->shouldIgnoreMissing(),
            builder: m::mock(BuilderInterface::class)->shouldIgnoreMissing(),
            pdo: $pdo,
        );

        $logger = m::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $logger->shouldReceive('info')->once()->with('SELECT * FROM sample_table WHERE id IN (1, 2, 3) ORDER BY id ASC', m::andAnyOtherArgs());
        $driver->setLogger($logger);

        $driver->query(
            'SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC',
            [1, 2, 3],
        );
    }

    public function testLogsQueryParameters(): void
    {
        $pdo = m::mock(PDOInterface::class)->shouldIgnoreMissing();
        $pdo->shouldReceive('prepare')->once()->andReturn(m::mock(PDOStatementInterface::class)->shouldIgnoreMissing());

        $driver = StubDriver::make(
            config: new SQLiteDriverConfig(options: [
                'logInterpolatedQueries' => false,
                'logQueryParameters' => true,
            ]),
            handler: m::mock(HandlerInterface::class)->shouldIgnoreMissing(),
            builder: m::mock(BuilderInterface::class)->shouldIgnoreMissing(),
            pdo: $pdo,
        );

        $logger = m::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $logger->shouldReceive('info')->once()->with('SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC', m::on(static function (array $context) {
            if (!isset($context['parameters'])) {
                return false;
            }

            $parametersAsString = \array_map('strval', $context['parameters']);

            $expectedParameters = ['1', '2', '3'];
            if ($parametersAsString !== $expectedParameters) {
                return false;
            }

            return isset($context['driver']);
        }), m::andAnyOtherArgs());
        $driver->setLogger($logger);

        $driver->query(
            'SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC',
            [1, 2, 3],
        );
    }

    public function testLogsWithEnabledInterpolationAndWithDatetimeMicroseconds(): void
    {
        $pdo = m::mock(PDOInterface::class)->shouldIgnoreMissing();
        $pdo->shouldReceive('prepare')->once()->andReturn(m::mock(PDOStatementInterface::class)->shouldIgnoreMissing());

        $driver = StubDriver::make(
            config: new SQLiteDriverConfig(options: ['logInterpolatedQueries' => true, 'withDatetimeMicroseconds' => true]),
            handler: m::mock(HandlerInterface::class)->shouldIgnoreMissing(),
            builder: m::mock(BuilderInterface::class)->shouldIgnoreMissing(),
            pdo: $pdo,
        );

        $date = new \DateTime('now');
        $logger = m::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $logger->shouldReceive('info')->once()->with(\sprintf(
            'SELECT * FROM sample_table WHERE name = \'%s\' AND registered > \'%s\'',
            'John Doe',
            $date->format('Y-m-d H:i:s.u'),
        ), m::andAnyOtherArgs());
        $driver->setLogger($logger);

        $driver->query('SELECT * FROM sample_table WHERE name = ? AND registered > ?', ['John Doe', $date]);
    }

    public function testUseCacheFromConfig(): void
    {
        $ref = new \ReflectionProperty(Driver::class, 'useCache');
        $ref->setAccessible(true);

        Assert::true($ref->getValue(StubDriver::create(new SQLiteDriverConfig(queryCache: true))));
        Assert::false($ref->getValue(StubDriver::create(new SQLiteDriverConfig(queryCache: false))));
    }

    public function testWithoutCache(): void
    {
        $ref = new \ReflectionProperty(Driver::class, 'useCache');
        $ref->setAccessible(true);

        $driver = StubDriver::create(new SQLiteDriverConfig(queryCache: true));

        Assert::true($ref->getValue($driver));
        Assert::false($ref->getValue($driver->withoutCache()));
    }

    public function testWithoutCacheTwice(): void
    {
        $driver = StubDriver::create(new SQLiteDriverConfig(queryCache: true));

        $ncDriver = $driver->withoutCache();

        Assert::same($ncDriver->withoutCache(), $ncDriver);
    }

    public function testWithoutCacheOnWithoutCacheInitially(): void
    {
        $driver = StubDriver::create(new SQLiteDriverConfig(queryCache: false));

        Assert::same($driver->withoutCache(), $driver);
    }

    public function testPdoNotClonedAfterCacheDisabled(): void
    {
        $ref = new \ReflectionMethod(Driver::class, 'getPDO');
        $ref->setAccessible(true);

        $driver = StubDriver::create(new SQLiteDriverConfig(queryCache: true));
        $oldPDO = $ref->invoke($driver);

        $driver = $driver->withoutCache();
        $newPDO = $ref->invoke($driver);

        Assert::same($newPDO, $oldPDO);
    }

    public function testPreparesARepeatedQueryOnce(): void
    {
        $pdo = m::mock(PDOInterface::class);
        $pdo->shouldReceive('prepare')->once()->andReturn(m::mock(PDOStatementInterface::class)->shouldIgnoreMissing());
        $driver = StubDriver::make(config: new SQLiteDriverConfig(queryCache: true), pdo: $pdo);

        $driver->query('SELECT 1');
        $driver->query('SELECT 1');
    }

    public function testRetriesAStatementOnceAfterALostConnection(): void
    {
        $pdo = m::mock(PDOInterface::class);
        $pdo->shouldReceive('prepare')->twice()->andReturnUsing(
            self::failingFirst(new \PDOException('server has gone away'), m::mock(PDOStatementInterface::class)->shouldIgnoreMissing()),
        );
        $driver = ConnectionLossDriver::make(config: new SQLiteDriverConfig(queryCache: false), pdo: $pdo);

        Assert::instanceOf($driver->query('SELECT 1'), StatementInterface::class);
    }

    public function testGivesUpWhenTheConnectionIsLostAgain(): never
    {
        $pdo = m::mock(PDOInterface::class);
        $pdo->shouldReceive('prepare')->twice()->andThrow(new \PDOException('server has gone away'));
        $driver = ConnectionLossDriver::make(config: new SQLiteDriverConfig(queryCache: false), pdo: $pdo);

        Expect::exception(StatementException\ConnectionException::class);

        $driver->query('SELECT 1');
    }

    public function testDoesNotRetryAStatementInsideATransaction(): never
    {
        $pdo = m::mock(PDOInterface::class);
        $pdo->shouldReceive('beginTransaction')->andReturn(true);
        $pdo->shouldReceive('prepare')->andReturnUsing(
            self::failingFirst(new \PDOException('server has gone away'), m::mock(PDOStatementInterface::class)->shouldIgnoreMissing()),
        );
        $driver = ConnectionLossDriver::make(config: new SQLiteDriverConfig(queryCache: false), pdo: $pdo);
        $driver->beginTransaction();

        Expect::exception(StatementException\ConnectionException::class);

        $driver->query('SELECT 1');
    }

    public function testDoesNotReconnectWhenBeginFailsForAnotherReason(): never
    {
        $pdo = m::mock(PDOInterface::class);
        $pdo->shouldReceive('beginTransaction')->andReturnUsing(self::failingFirst(new \PDOException('boom'), true));
        $driver = ConnectionLossDriver::make(config: new SQLiteDriverConfig(reconnect: true), pdo: $pdo);

        Expect::exception(StatementException::class)->withMessage('boom');

        $driver->beginTransaction();
    }

    public function testReturnsTheResultOfBeginAfterReconnecting(): void
    {
        $pdo = m::mock(PDOInterface::class);
        $pdo->shouldReceive('beginTransaction')->twice()->andReturnUsing(
            self::failingFirst(new \PDOException('server has gone away'), false),
        );
        $driver = ConnectionLossDriver::make(config: new SQLiteDriverConfig(reconnect: true), pdo: $pdo);

        Assert::false($driver->beginTransaction());
        Assert::same($driver->getTransactionLevel(), 1);
    }

    public function testGivesUpWhenBeginFailsAgainAfterReconnecting(): void
    {
        $pdo = m::mock(PDOInterface::class);
        $pdo->shouldReceive('beginTransaction')->twice()->andThrow(new \PDOException('server has gone away'));
        $driver = ConnectionLossDriver::make(config: new SQLiteDriverConfig(reconnect: true), pdo: $pdo);

        try {
            $driver->beginTransaction();
            Assert::fail('The second lost connection must be thrown.');
        } catch (StatementException\ConnectionException) {
        }

        Assert::same($driver->getTransactionLevel(), 0);
    }

    public function testMapsAFailedCommit(): never
    {
        $pdo = m::mock(PDOInterface::class);
        $pdo->shouldReceive('beginTransaction')->andReturn(true);
        $pdo->shouldReceive('inTransaction')->andReturn(true);
        $pdo->shouldReceive('commit')->andThrow(new \PDOException('commit failed'));
        $driver = ConnectionLossDriver::make(config: new SQLiteDriverConfig(), pdo: $pdo);
        $driver->beginTransaction();

        Expect::exception(StatementException::class)->withMessage('commit failed');

        $driver->commitTransaction();
    }

    public function testMapsAFailedRollback(): never
    {
        $pdo = m::mock(PDOInterface::class);
        $pdo->shouldReceive('beginTransaction')->andReturn(true);
        $pdo->shouldReceive('inTransaction')->andReturn(true);
        $pdo->shouldReceive('rollBack')->andThrow(new \PDOException('rollback failed'));
        $driver = ConnectionLossDriver::make(config: new SQLiteDriverConfig(), pdo: $pdo);
        $driver->beginTransaction();

        Expect::exception(StatementException::class)->withMessage('rollback failed');

        $driver->rollbackTransaction();
    }

    public function testQuotesTheValueOfABackedEnum(): void
    {
        $pdo = m::mock(PDOInterface::class);
        $pdo->shouldReceive('quote')->once()->with('foo', \PDO::PARAM_STR)->andReturn("'foo'");
        $driver = StubDriver::make(pdo: $pdo);

        Assert::same($driver->quote(FooBarEnum::FOO), "'foo'");
    }

    public function testRefusesToConnectWithoutAPdoConnectionConfig(): never
    {
        $config = new class(new class extends ConnectionConfig {}, StubDriver::class) extends DriverConfig {};
        $driver = StubDriver::create($config);

        Expect::exception(\InvalidArgumentException::class)
            ->withMessage('Could not establish PDO connection using non-PDO configuration');

        $driver->connect();
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->driver = StubDriver::create(new SQLiteDriverConfig());
    }

    /**
     * A callback that throws on its first call and returns the result on every later one.
     */
    private static function failingFirst(\Throwable $exception, mixed $result): \Closure
    {
        $calls = 0;

        return static function () use (&$calls, $exception, $result): mixed {
            return ++$calls === 1 ? throw $exception : $result;
        };
    }

    private function checkImmutability(DriverInterface $driver, DriverInterface $newDriver): void
    {
        // Immutability
        Assert::notSame($newDriver, $driver);
        Assert::notSame($newDriver->getSchemaHandler(), $driver->getSchemaHandler());
        Assert::notSame($newDriver->getQueryBuilder(), $driver->getQueryBuilder());
    }
}

/**
 * Maps a "gone away" failure to a lost connection and anything else to a plain statement error.
 */
final class ConnectionLossDriver extends StubDriver
{
    protected function mapException(\Throwable $exception, string $query): StatementException
    {
        return \str_contains($exception->getMessage(), 'gone away')
            ? new StatementException\ConnectionException($exception, $query)
            : new StatementException($exception, $query);
    }
}
