<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database;

use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Exception\StatementException;
use Spiral\Database\Query\DeleteQuery;
use Spiral\Database\Query\InsertQuery;
use Spiral\Database\Query\SelectQuery;
use Spiral\Database\Query\UpdateQuery;

/**
 * DatabaseInterface is high level abstraction used to represent single database. You must always
 * check database type using getType() method before writing plain SQL for execute and query methods
 * (unless you are locking your module/application to one database).
 */
interface DatabaseInterface
{
    // Driver types
    public const WRITE = 0;
    public const READ  = 1;

    // Known database types. More to be added?
    public const MYSQL      = 'MySQL';
    public const POSTGRES   = 'Postgres';
    public const SQLITE     = 'SQLite';
    public const SQL_SERVER = 'SQLServer';

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * Database type matched to one of database constants. You MUST write SQL for execute and query
     * methods by respecting result of this method.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * @param int $type
     * @return DriverInterface
     */
    public function getDriver(int $type = self::WRITE): DriverInterface;

    /**
     * Return database with new isolation prefix.
     *
     * @param string $prefix
     * @param bool   $add
     * @return self|$this
     */
    public function withPrefix(string $prefix, bool $add = true): self;

    /**
     * @return string
     */
    public function getPrefix(): string;

    /**
     * Check if table exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasTable(string $name): bool;

    /**
     * Get all associated database tables.
     *
     * @return TableInterface[]
     */
    public function getTables(): array;

    /**
     * @param string $name
     * @return TableInterface
     */
    public function table(string $name): TableInterface;

    /**
     * Execute statement and return number of affected rows.
     *
     * @param string $query
     * @param array  $parameters Parameters to be binded into query.
     * @return int
     *
     * @throws StatementException
     */
    public function execute(string $query, array $parameters = []): int;

    /**
     * Execute statement and return query iterator.
     *
     * @param string $query
     * @param array  $parameters Parameters to be binded into query.
     * @return StatementInterface
     *
     * @throws StatementException
     */
    public function query(string $query, array $parameters = []): StatementInterface;

    /**
     * Get instance of InsertBuilder associated with current Database.
     *
     * @param string $table Table where values should be inserted to.
     * @return InsertQuery
     */
    public function insert(string $table = ''): InsertQuery;

    /**
     * Get instance of UpdateBuilder associated with current Database.
     *
     * @param string $table Table where rows should be updated in.
     * @param array  $values Initial set of columns to update associated with their values.
     * @param array  $where Initial set of where rules specified as array.
     * @return UpdateQuery
     */
    public function update(string $table = '', array $values = [], array $where = []): UpdateQuery;

    /**
     * Get instance of DeleteBuilder associated with current Database.
     *
     * @param string $table Table where rows should be deleted from.
     * @param array  $where Initial set of where rules specified as array.
     * @return DeleteQuery
     */
    public function delete(string $table = '', array $where = []): DeleteQuery;

    /**
     * Get instance of SelectBuilder associated with current Database.
     *
     * @param array|string $columns Columns to select.
     * @return SelectQuery
     */
    public function select($columns = '*'): SelectQuery;

    /**
     * Execute multiple commands defined by Closure function inside one transaction. Closure or
     * function must receive only one argument - DatabaseInterface instance.
     *
     * @link http://en.wikipedia.org/wiki/Database_transaction
     * @param callable $callback
     * @param string   $isolationLevel
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(callable $callback, string $isolationLevel = null);

    /**
     * Start database transaction.
     *
     * @link http://en.wikipedia.org/wiki/Database_transaction
     * @param string $isolationLevel
     * @return bool
     */
    public function begin(string $isolationLevel = null): bool;

    /**
     * Commit the active database transaction.
     *
     * @return bool
     */
    public function commit(): bool;

    /**
     * Rollback the active database transaction.
     *
     * @return bool
     */
    public function rollback(): bool;
}
