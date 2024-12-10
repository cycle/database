<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\Query\UpdateQuery;

/**
 * DatabaseInterface is high level abstraction used to represent single database. You must always
 * check database type using getType() method before writing plain SQL for execute and query methods
 * (unless you are locking your module/application to one database).
 *
 * @method DatabaseInterface withoutCache() Get a new Database instance without query cache or the same instance
 *         if no cache is used. Will be added the next major release.
 */
interface DatabaseInterface
{
    // Driver types
    public const WRITE = 0;
    public const READ = 1;

    /**
     * @psalm-return non-empty-string
     */
    public function getName(): string;

    /**
     * Database type matched to one of database constants. You MUST write SQL for execute and query
     * methods by respecting result of this method.
     *
     * @psalm-return non-empty-string
     */
    public function getType(): string;

    public function getDriver(int $type = self::WRITE): DriverInterface;

    /**
     * Return database with new isolation prefix.
     */
    public function withPrefix(string $prefix, bool $add = true): self;

    public function getPrefix(): string;

    /**
     * Check if table exists.
     *
     * @psalm-param non-empty-string $name
     */
    public function hasTable(string $name): bool;

    /**
     * Get all associated database tables.
     *
     * @return TableInterface[]
     */
    public function getTables(): array;

    /**
     * @psalm-param non-empty-string $name
     */
    public function table(string $name): TableInterface;

    /**
     * Execute statement and return number of affected rows.
     *
     * @psalm-param non-empty-string $query
     *
     * @param array $parameters Parameters to be binded into query.
     *
     * @throws StatementException
     */
    public function execute(string $query, array $parameters = []): int;

    /**
     * Execute statement and return query iterator.
     *
     * @psalm-param non-empty-string $query
     *
     * @param array  $parameters Parameters to be binded into query.
     *
     * @throws StatementException
     */
    public function query(string $query, array $parameters = []): StatementInterface;

    /**
     * Get instance of InsertBuilder associated with current Database.
     *
     * @param string $table Table where values should be inserted to.
     *
     * @see self::withoutCache() May be useful to disable query cache for batch inserts.
     */
    public function insert(string $table = ''): InsertQuery;

    /**
     * Get instance of UpdateBuilder associated with current Database.
     *
     * @param string $table  Table where rows should be updated in.
     * @param array  $values Initial set of columns to update associated with their values.
     * @param array  $where  Initial set of where rules specified as array.
     */
    public function update(string $table = '', array $values = [], array $where = []): UpdateQuery;

    /**
     * Get instance of DeleteBuilder associated with current Database.
     *
     * @param string $table Table where rows should be deleted from.
     * @param array  $where Initial set of where rules specified as array.
     */
    public function delete(string $table = '', array $where = []): DeleteQuery;

    /**
     * Get instance of SelectBuilder associated with current Database.
     *
     * @param array|string $columns Columns to select.
     */
    public function select(mixed $columns = '*'): SelectQuery;

    /**
     * Execute multiple commands defined by Closure function inside one transaction. Closure or
     * function must receive only one argument - DatabaseInterface instance.
     *
     * @link http://en.wikipedia.org/wiki/Database_transaction
     *
     * @template CallbackResult
     *
     * @param callable(DatabaseInterface): CallbackResult $callback
     *
     * @return CallbackResult
     * @throws \Throwable
     *
     */
    public function transaction(callable $callback, ?string $isolationLevel = null): mixed;

    /**
     * Start database transaction.
     *
     * @link http://en.wikipedia.org/wiki/Database_transaction
     */
    public function begin(?string $isolationLevel = null): bool;

    /**
     * Commit the active database transaction.
     */
    public function commit(): bool;

    /**
     * Rollback the active database transaction.
     */
    public function rollback(): bool;
}
