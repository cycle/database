<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Stub\Driver;

use Closure;
use Cycle\Database\Driver\PDOStatementInterface;
use JetBrains\PhpStorm\ArrayShape;
use PDO;
use stdClass;
use Traversable;

class TestPDOStatement implements PDOStatementInterface
{
    private \PDOStatement $statement;
    /** @var null|Closure(\PDOStatement $pdo, ?array $params): bool */
    private ?Closure $queryCallback;

    /**
     * @param null|Closure(\PDOStatement $pdo, ?array $params): bool $queryCallback
     */
    public function __construct(\PDOStatement $statement, ?Closure $queryCallback = null)
    {
        $this->statement = $statement;
        $this->queryCallback = $queryCallback;
    }

    public function getIterator(): Traversable
    {
        return $this->statement->getIterator();
    }

    public function execute(?array $params = null): bool
    {
        return $this->queryCallback !== null
            ? ($this->queryCallback)($this->statement, $params)
            : $this->statement->execute(...\func_get_args());
    }

    public function fetch(
        int $mode = PDO::FETCH_BOTH,
        int $cursorOrientation = PDO::FETCH_ORI_NEXT,
        int $cursorOffset = 0,
    ): mixed {
        return $this->statement->fetch(...\func_get_args());
    }

    public function bindParam(
        int|string $param,
        mixed &$var,
        int $type = PDO::PARAM_STR,
        int $maxLength = null,
        mixed $driverOptions = null,
    ): bool {
        return $this->statement->bindParam(...\func_get_args());
    }

    public function bindColumn(
        int|string $column,
        mixed &$var,
        int $type = PDO::PARAM_STR,
        int $maxLength = null,
        mixed $driverOptions = null,
    ): bool {
        return $this->statement->bindColumn(...\func_get_args());
    }

    public function bindValue(int|string $param, mixed $value, int $type = PDO::PARAM_STR,): bool
    {
        return $this->statement->bindValue(...\func_get_args());
    }

    public function rowCount(): int
    {
        return $this->statement->rowCount(...\func_get_args());
    }

    public function fetchColumn(int $column = 0): mixed
    {
        return $this->statement->fetchColumn(...\func_get_args());
    }

    public function fetchAll(int $mode = PDO::FETCH_BOTH, ...$args): array
    {
        return $this->statement->fetchAll(...\func_get_args());
    }

    public function fetchObject(?string $class = stdClass::class, array $constructorArgs = []): object|false
    {
        return $this->statement->fetchObject(...\func_get_args());
    }

    public function errorCode(): ?string
    {
        return $this->statement->errorCode(...\func_get_args());
    }

    #[ArrayShape([0 => 'string', 1 => 'int', 2 => 'string'])] public function errorInfo(): array
    {
        return $this->statement->errorInfo(...\func_get_args());
    }

    public function setAttribute(int $attribute, mixed $value): bool
    {
        return $this->statement->setAttribute(...\func_get_args());
    }

    public function getAttribute(int $name): mixed
    {
        return $this->statement->getAttribute(...\func_get_args());
    }

    public function columnCount(): int
    {
        return $this->statement->columnCount(...\func_get_args());
    }

    public function getColumnMeta(int $column): array|false
    {
        return $this->statement->getColumnMeta(...\func_get_args());
    }

    public function setFetchMode($mode, $className = null, ...$params)
    {
        return $this->statement->setFetchMode(...\func_get_args());
    }

    public function nextRowset(): bool
    {
        return $this->statement->nextRowset(...\func_get_args());
    }

    public function closeCursor(): bool
    {
        return $this->statement->closeCursor(...\func_get_args());
    }

    public function debugDumpParams(): ?bool
    {
        return $this->statement->debugDumpParams(...\func_get_args());
    }
}
