<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Connection;

use Cycle\Database\Exception\StatementException;
use Cycle\Database\Exception\StatementException\ConnectionException;
use Cycle\Database\Tests\Stub\Driver\MSSQLWrapDriver;
use Cycle\Database\Tests\Stub\Driver\MysqlWrapDriver;
use Cycle\Database\Tests\Stub\Driver\PostgresWrapDriver;
use Cycle\Database\Tests\Stub\Driver\SQLiteWrapDriver;

/**
 * @runInSeparateProcess
 */
abstract class ConnectionExceptionTest extends BaseConnectionTest
{
    /**
     * @return iterable<\Exception>
     */
    abstract public function reconnectableExceptionsProvider(): iterable;

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('reconnectableExceptionsProvider')]
    public function testConnectionExceptionOutOfTransaction(\Exception $exception): void
    {
        $driver = $this->getDriver();
        $this->configureExceptionsQuery($driver, [$exception]);

        $result = $driver->query('SELECT 42')->fetchColumn(0);

        \Testo\Assert::same((string) $result, '42');
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('reconnectableExceptionsProvider')]
    public function testConnectionExceptionInTransaction(\Exception $exception): void
    {
        $driver = $this->getDriver();
        $driver->beginTransaction();
        $this->configureExceptionsQuery($driver, [$exception]);

        \Testo\Expect::exception(ConnectionException::class);

        try {
            $driver->query('SELECT 42')->fetchColumn(0);
        } finally {
            $driver->rollbackTransaction();
        }
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('reconnectableExceptionsProvider')]
    public function testConnectionExceptionReconnectsOnce(\Exception $exception): void
    {
        $driver = $this->getDriver();
        $this->configureExceptionsQuery($driver, [$exception, $exception]);

        \Testo\Expect::exception(ConnectionException::class);

        $driver->query('SELECT 42')->fetchColumn(0);
    }

    #[\Testo\Test]
    public function testNonConnectionExceptionOutOfTransaction(): void
    {
        $driver = $this->getDriver();
        $this->configureExceptionsQuery($driver, [
            new \RuntimeException('Test exception 42.'),
        ]);

        \Testo\Expect::exception(StatementException::class)->withMessageContaining('Test exception 42.');

        $driver->query('SELECT 42')->fetch();
    }

    private function configureExceptionsQuery(
        SQLiteWrapDriver|MysqlWrapDriver|PostgresWrapDriver|MSSQLWrapDriver $driver,
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
