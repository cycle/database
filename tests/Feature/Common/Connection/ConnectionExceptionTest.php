<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Connection;

use Cycle\Database\Exception\StatementException;
use Cycle\Database\Exception\StatementException\ConnectionException;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\Driver;
use Cycle\Testing\Database\Mock\WrapDriverInterface;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

abstract class ConnectionExceptionTest extends BaseConnectionTest
{
    /**
     * @return iterable<\Exception>
     */
    abstract public function reconnectableExceptionsProvider(): iterable;

    #[Test]
    #[DataProvider('reconnectableExceptionsProvider')]
    #[ExceptDriver(Driver::SQLite)]
    public function testConnectionExceptionOutOfTransaction(\Exception $exception): void
    {
        $driver = $this->wrapDriver();
        $this->configureExceptionsQuery($driver, [$exception]);

        $result = $driver->query('SELECT 42')->fetchColumn(0);

        Assert::same((string) $result, '42');
    }

    #[Test]
    #[DataProvider('reconnectableExceptionsProvider')]
    #[ExceptDriver(Driver::SQLite)]
    public function testConnectionExceptionInTransaction(\Exception $exception): void
    {
        $driver = $this->wrapDriver();
        $driver->beginTransaction();
        $this->configureExceptionsQuery($driver, [$exception]);

        Expect::exception(ConnectionException::class);

        try {
            $driver->query('SELECT 42')->fetchColumn(0);
        } finally {
            $driver->rollbackTransaction();
        }
    }

    #[Test]
    #[DataProvider('reconnectableExceptionsProvider')]
    #[ExceptDriver(Driver::SQLite)]
    public function testConnectionExceptionReconnectsOnce(\Exception $exception): void
    {
        $driver = $this->wrapDriver();
        $this->configureExceptionsQuery($driver, [$exception, $exception]);

        Expect::exception(ConnectionException::class);

        $driver->query('SELECT 42')->fetchColumn(0);
    }

    #[Test]
    public function testNonConnectionExceptionOutOfTransaction(): void
    {
        $driver = $this->wrapDriver();
        $this->configureExceptionsQuery($driver, [
            new \RuntimeException('Test exception 42.'),
        ]);

        Expect::exception(StatementException::class)->withMessageContaining('Test exception 42.');

        $driver->query('SELECT 42')->fetch();
    }

    private function configureExceptionsQuery(
        WrapDriverInterface $driver,
        array $exceptions,
    ): void {
        $driver->setQueryCallback(static function (\PDOStatement $statement, ?array $params) use (&$exceptions) {
            if ($exceptions !== []) {
                throw \array_shift($exceptions);
            }
            return $statement->execute($params);
        });
    }
}
