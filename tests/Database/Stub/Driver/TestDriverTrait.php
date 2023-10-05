<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Stub\Driver;

use Closure;
use Cycle\Database\Driver\PDOInterface;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Exception\StatementException\ConnectionException;

trait TestDriverTrait
{
    public int $disconnectCalls = 0;
    public int $exceptionOnTransactionBegin = 0;
    /** @var Closure(\PDOStatement $pdo, ?array $params): bool|null */
    public ?Closure $queryCallback = null;

    protected function getPDO(): PDOInterface
    {
        $pdo = parent::getPDO();

        return new TestPDO(
            pdo: $pdo,
            exceptionOnTransactionBegin: $this->exceptionOnTransactionBegin,
            queryCallback: $this->queryCallback,
        );
    }

    /**
     * @param Closure(\PDOStatement $pdo, ?array $params): bool $callback
     */
    public function setQueryCallback(Closure $callback): void
    {
        $this->queryCallback = $callback;
    }

    protected function mapException(\Throwable $exception, string $query): StatementException
    {
        if ($exception instanceof ConnectionException) {
            return $exception;
        }
        return parent::mapException($exception, $query);
    }

    public function disconnect(): void
    {
        ++$this->disconnectCalls;
        parent::disconnect();
    }

    public function setDefaults(): void
    {
        $this->disconnectCalls = 0;
        $this->exceptionOnTransactionBegin = 0;
        $this->queryCallback = null;
    }
}
