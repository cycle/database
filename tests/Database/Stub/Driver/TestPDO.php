<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Stub\Driver;

use Closure;
use Cycle\Database\Driver\PDOInterface;
use Cycle\Database\Driver\PDOStatementInterface;
use Cycle\Database\Exception\StatementException\ConnectionException;
use Exception;
use PDO;
use PDOStatement;

class TestPDO implements PDOInterface
{
    private PDO $pdo;
    private int $exceptionOnTransactionBegin;
    /** @var Closure(\PDOStatement, ?array): bool|null */
    private ?Closure $queryCallback;

    /**
     * @param Closure(\PDOStatement $pdo, ?array $params): bool|null $queryCallback
     */
    public function __construct(PDO $pdo, int &$exceptionOnTransactionBegin, ?Closure $queryCallback)
    {
        $this->pdo = $pdo;
        $this->exceptionOnTransactionBegin = &$exceptionOnTransactionBegin;
        $this->queryCallback = $queryCallback;
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->pdo->$name(...$arguments);
    }

    public function beginTransaction(): bool
    {
        if ($this->exceptionOnTransactionBegin > 0) {
            $this->exceptionOnTransactionBegin--;

            throw new ConnectionException(new Exception(), 'Test exception');
        }

        return $this->pdo->beginTransaction();
    }

    public function prepare(string $query, array $options = []): PDOStatementInterface|false
    {
        $statement = $this->pdo->prepare(...\func_get_args());

        return $this->prepareStatement($statement);
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

    public function query($statement, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, ...$fetch_mode_args): TestPDOStatement|false
    {
        $statement = $this->pdo->query(...\func_get_args());

        return $this->prepareStatement($statement);
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

    private function prepareStatement(PDOStatement|false $statement): TestPDOStatement|false
    {
        return $statement === false ? false : new TestPDOStatement(
            statement: $statement,
            queryCallback: $this->queryCallback
        );
    }
}
