<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database;

use Spiral\Database\QueryStatement;
use Spiral\Database\Exception\QueryException;

/**
 * DatabaseInterface is high level abstraction used to represent single database. You must always
 * check database type using getType() method before writing plain SQL for execute and query methods
 * (unless you are locking your module/application to one database).
 */
interface DatabaseInterface
{
    /**
     * Known database types. More to be added?
     */
    const MYSQL      = 'MySQL';
    const POSTGRES   = 'Postgres';
    const SQLITE     = 'SQLite';
    const SQL_SERVER = 'SQLServer';

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
     * Execute statement and return number of affected rows.
     *
     * @param string $query
     * @param array  $parameters Parameters to be binded into query.
     *
     * @return int
     *
     * @throws QueryException
     */
    public function execute(string $query, array $parameters = []): int;

    /**
     * Execute statement and return query iterator.
     *
     * @param string $query
     * @param array  $parameters Parameters to be binded into query.
     *
     * @return QueryStatement
     *
     * @throws QueryException
     */
    public function query(string $query, array $parameters = []);

    /**
     * Execute multiple commands defined by Closure function inside one transaction. Closure or
     * function must receive only one argument - DatabaseInterface instance.
     *
     * @param callable $callback
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function transaction(callable $callback);

    /**
     * Start database transaction.
     *
     * @link http://en.wikipedia.org/wiki/Database_transaction
     *
     * @return bool
     */
    public function begin();

    /**
     * Commit the active database transaction.
     *
     * @return bool
     */
    public function commit();

    /**
     * Rollback the active database transaction.
     *
     * @return bool
     */
    public function rollback();

    /**
     * Check if table exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasTable(string $name): bool;
}
