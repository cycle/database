<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Stub;

use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Query\BuilderInterface;
use Cycle\Database\StatementInterface;

/**
 * A driver that is not a {@see \Cycle\Database\Driver\Driver}: it implements only the interface and
 * hands every call to an SQLite driver, recording the transaction calls it receives.
 */
final class DecoratedDriver implements DriverInterface
{
    /** @var list<array{string, list<mixed>}> */
    public array $transactionCalls = [];

    private function __construct(
        private readonly SQLiteDriver $driver,
    ) {}

    public static function create(DriverConfig $config): self
    {
        return new self(SQLiteDriver::create($config));
    }

    public function isReadonly(): bool
    {
        return $this->driver->isReadonly();
    }

    public function getType(): string
    {
        return $this->driver->getType();
    }

    public function getTimezone(): \DateTimeZone
    {
        return $this->driver->getTimezone();
    }

    public function getSchemaHandler(): HandlerInterface
    {
        return $this->driver->getSchemaHandler();
    }

    public function getQueryCompiler(): CompilerInterface
    {
        return $this->driver->getQueryCompiler();
    }

    public function getQueryBuilder(): BuilderInterface
    {
        return $this->driver->getQueryBuilder();
    }

    public function connect(): void
    {
        $this->driver->connect();
    }

    public function isConnected(): bool
    {
        return $this->driver->isConnected();
    }

    public function disconnect(): void
    {
        $this->driver->disconnect();
    }

    public function quote(mixed $value, int $type = \PDO::PARAM_STR): string
    {
        return $this->driver->quote($value, $type);
    }

    public function query(string $statement, array $parameters = []): StatementInterface
    {
        return $this->driver->query($statement, $parameters);
    }

    public function execute(string $query, array $parameters = []): int
    {
        return $this->driver->execute($query, $parameters);
    }

    public function lastInsertID(?string $sequence = null)
    {
        return $this->driver->lastInsertID($sequence);
    }

    public function beginTransaction(?string $isolationLevel = null): bool
    {
        $this->transactionCalls[] = ['begin', \func_get_args()];

        return $this->driver->beginTransaction($isolationLevel);
    }

    public function commitTransaction(): bool
    {
        $this->transactionCalls[] = ['commit', []];

        return $this->driver->commitTransaction();
    }

    public function rollbackTransaction(): bool
    {
        $this->transactionCalls[] = ['rollback', []];

        return $this->driver->rollbackTransaction();
    }

    public function getTransactionLevel(): int
    {
        return $this->driver->getTransactionLevel();
    }
}
