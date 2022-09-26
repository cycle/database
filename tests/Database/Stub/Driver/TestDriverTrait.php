<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Stub\Driver;

use Cycle\Database\Driver\PdoInterface;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Exception\StatementException\ConnectionException;
use Exception;
use PDO;
use PDOStatement;

trait TestDriverTrait
{
    public int $disconnectCalls = 0;
    public int $exceptionOnTransactionBegin = 0;

    protected function getPDO(): PdoInterface
    {
        $pdo = parent::getPDO();

        return new class ($pdo, $this->exceptionOnTransactionBegin) implements PdoInterface {
            private PDO $pdo;
            private int $exceptionOnTransactionBegin;

            public function __construct(PDO $pdo, int &$exceptionOnTransactionBegin)
            {
                $this->pdo = $pdo;
                $this->exceptionOnTransactionBegin = &$exceptionOnTransactionBegin;
            }

            public function __call(string $name, array $arguments): mixed
            {
                return $this->pdo->$name(...$arguments);
            }

            public function beginTransaction(): bool
            {
                if ($this->exceptionOnTransactionBegin > 0) {
                    --$this->exceptionOnTransactionBegin;
                    throw new ConnectionException(new Exception(), 'Test exception');
                }
                return $this->pdo->beginTransaction();
            }

            public function prepare(string $query, array $options = []): PDOStatement|false
            {
                return $this->pdo->{__FUNCTION__}(...\func_get_args());
            }

            public function commit(): bool
            {
                return $this->pdo->{__FUNCTION__}(...\func_get_args());
            }

            public function rollBack(): bool
            {
                return $this->pdo->{__FUNCTION__}(...\func_get_args());
            }

            public function inTransaction(): bool
            {
                return $this->pdo->{__FUNCTION__}(...\func_get_args());
            }

            public function exec(string $statement): int|false
            {
                return $this->pdo->{__FUNCTION__}(...\func_get_args());
            }

            public function query($statement, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, ...$fetch_mode_args)
            {
                return $this->pdo->{__FUNCTION__}(...\func_get_args());
            }

            public function lastInsertId(?string $name = null): string|false
            {
                return $this->pdo->{__FUNCTION__}(...\func_get_args());
            }

            public function setAttribute(int $attribute, mixed $value): bool
            {
                return $this->pdo->{__FUNCTION__}(...\func_get_args());
            }

            public function getAttribute(int $attribute): mixed
            {
                return $this->pdo->{__FUNCTION__}(...\func_get_args());
            }

            public function errorCode(): ?string
            {
                return $this->pdo->{__FUNCTION__}(...\func_get_args());
            }

            public function errorInfo(): array
            {
                return $this->pdo->{__FUNCTION__}(...\func_get_args());
            }

            public function quote(string $string, int $type = PDO::PARAM_STR): string|false
            {
                return $this->pdo->{__FUNCTION__}(...\func_get_args());
            }
        };
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
    }
}
