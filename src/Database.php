<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database;

use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\Query\UpdateQuery;

/**
 * Database class is high level abstraction at top of Driver. Databases usually linked to real
 * database or logical portion of database (filtered by prefix).
 */
final class Database implements DatabaseInterface
{
    // Isolation levels for transactions
    public const ISOLATION_SERIALIZABLE = DriverInterface::ISOLATION_SERIALIZABLE;
    public const ISOLATION_REPEATABLE_READ = DriverInterface::ISOLATION_REPEATABLE_READ;
    public const ISOLATION_READ_COMMITTED = DriverInterface::ISOLATION_READ_COMMITTED;
    public const ISOLATION_READ_UNCOMMITTED = DriverInterface::ISOLATION_READ_UNCOMMITTED;

    /**
     * @psalm-param non-empty-string $name Internal database name/id.
     *
     * @param string $prefix Default database table prefix, will be used for all table identifiers.
     * @param DriverInterface $driver Driver instance responsible for database connection.
     * @param DriverInterface|null $readDriver Read-only driver connection.
     */
    public function __construct(
        private string $name,
        private string $prefix,
        private DriverInterface $driver,
        private ?DriverInterface $readDriver = null,
    ) {}

    /**
     * @psalm-return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getType(): string
    {
        return $this->getDriver(self::WRITE)->getType();
    }

    public function getDriver(int $type = DatabaseInterface::WRITE): DriverInterface
    {
        return $type === self::READ && $this->readDriver !== null ? $this->readDriver : $this->driver;
    }

    public function withPrefix(string $prefix, bool $add = true): DatabaseInterface
    {
        $database = clone $this;

        $add ? $database->prefix .= $prefix : $database->prefix = $prefix;

        return $database;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @psalm-param non-empty-string $name
     */
    public function hasTable(string $name): bool
    {
        return $this->getDriver()->getSchemaHandler()->hasTable($this->prefix . $name);
    }

    /**
     * @return Table[]
     */
    public function getTables(): array
    {
        $schemaHandler = $this->getDriver(self::READ)->getSchemaHandler();

        $result = [];
        foreach ($schemaHandler->getTableNames($this->prefix) as $table) {
            $table = \str_contains($table, '.')
                ? \str_replace('.' . $this->prefix, '.', $table)
                : \substr($table, \strlen($this->prefix));

            $result[] = new Table($this, $table);
        }

        return $result;
    }

    /**
     * @psalm-param non-empty-string $name
     */
    public function table(string $name): Table
    {
        return new Table($this, $name);
    }

    /**
     * @psalm-param non-empty-string $query
     */
    public function execute(string $query, array $parameters = []): int
    {
        return $this->getDriver(self::WRITE)
            ->execute($query, $parameters);
    }

    /**
     * @psalm-param non-empty-string $query
     */
    public function query(string $query, array $parameters = []): StatementInterface
    {
        return $this->getDriver(self::READ)
            ->query($query, $parameters);
    }

    public function insert(?string $table = null): InsertQuery
    {
        return $this->getDriver(self::WRITE)
            ->getQueryBuilder()
            ->insertQuery($this->prefix, $table);
    }

    public function update(?string $table = null, array $values = [], array $where = []): UpdateQuery
    {
        return $this->getDriver(self::WRITE)
            ->getQueryBuilder()
            ->updateQuery($this->prefix, $table, $where, $values);
    }

    public function delete(?string $table = null, array $where = []): DeleteQuery
    {
        return $this->getDriver(self::WRITE)
            ->getQueryBuilder()
            ->deleteQuery($this->prefix, $table, $where);
    }

    public function select(mixed $columns = '*'): SelectQuery
    {
        $arguments = \func_get_args();
        if (isset($arguments[0]) && \is_array($arguments[0])) {
            //Can be required in some cases while collecting data from Table->select(), stupid bug.
            $arguments = $arguments[0];
        }

        return $this->getDriver(self::READ)
            ->getQueryBuilder()
            ->selectQuery($this->prefix, [], $arguments);
    }

    public function transaction(
        callable $callback,
        ?string $isolationLevel = null,
    ): mixed {
        $this->begin($isolationLevel);

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function begin(?string $isolationLevel = null): bool
    {
        return $this->getDriver(self::WRITE)->beginTransaction($isolationLevel);
    }

    public function commit(): bool
    {
        return $this->getDriver(self::WRITE)->commitTransaction();
    }

    public function rollback(): bool
    {
        return $this->getDriver(self::WRITE)->rollbackTransaction();
    }

    public function withoutCache(): self
    {
        $database = clone $this;

        if ($this->readDriver instanceof Driver && $database->readDriver !== $database->driver) {
            $database->readDriver = $database->readDriver->withoutCache();
        }

        if ($this->driver instanceof Driver) {
            $database->driver = $database->readDriver === $database->driver
                ? ($database->readDriver = $database->driver->withoutCache())
                : $database->driver->withoutCache();

            return $database;
        }

        return $this;
    }

    /**
     * Shortcut to get table abstraction.
     *
     * @psalm-param non-empty-string $name Table name without prefix.
     */
    public function __get(string $name): TableInterface
    {
        return $this->table($name);
    }
}
