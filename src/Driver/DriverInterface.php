<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use PDO;
use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Exception\ReadonlyConnectionException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\BuilderInterface;
use Cycle\Database\StatementInterface;

/**
 * Wraps PDO connection and provides common abstractions over database operations.
 */
interface DriverInterface
{
    /**
     * Transaction isolation level 'SERIALIZABLE'.
     *
     * This is the highest isolation level. With a lock-based concurrency control DBMS
     * implementation, serializability requires read and write locks (acquired on selected data) to
     * be released at the end of the transaction. Also range-locks must be acquired when a SELECT
     * query uses a ranged WHERE clause, especially to avoid the phantom reads phenomenon (see
     * below).
     *
     * When using non-lock based concurrency control, no locks are acquired; however, if the system
     * detects a write collision among several concurrent transactions, only one of them is allowed
     * to commit. See snapshot isolation for more details on this topic.
     *
     * @link http://en.wikipedia.org/wiki/Isolation_(database_systems)
     */
    public const ISOLATION_SERIALIZABLE = 'SERIALIZABLE';

    /**
     * Transaction isolation level 'REPEATABLE READ'.
     *
     * In this isolation level, a lock-based concurrency control DBMS implementation keeps read and
     * write locks (acquired on selected data) until the end of the transaction. However,
     * range-locks are not managed, so phantom reads can occur.
     *
     * @link http://en.wikipedia.org/wiki/Isolation_(database_systems)
     */
    public const ISOLATION_REPEATABLE_READ = 'REPEATABLE READ';

    /**
     * Transaction isolation level 'READ COMMITTED'.
     *
     * In this isolation level, a lock-based concurrency control DBMS implementation keeps write
     * locks
     * (acquired on selected data) until the end of the transaction, but read locks are released as
     * soon as the SELECT operation is performed (so the non-repeatable reads phenomenon can occur
     * in this isolation level, as discussed below). As in the previous level, range-locks are not
     * managed.
     *
     * Putting it in simpler words, read committed is an isolation level that guarantees that any
     * data read is committed at the moment it is read. It simply restricts the reader from seeing
     * any intermediate, uncommitted, 'dirty' read. It makes no promise whatsoever that if the
     * transaction re-issues the read, it will find the same data; data is free to change after it
     * is read.
     *
     * @link http://en.wikipedia.org/wiki/Isolation_(database_systems)
     */
    public const ISOLATION_READ_COMMITTED = 'READ COMMITTED';

    /**
     * Transaction isolation level 'READ UNCOMMITTED'.
     *
     * This is the lowest isolation level. In this level, dirty reads are allowed, so one
     * transaction may see not-yet-committed changes made by other transactions.
     *
     * Since each isolation level is stronger than those below, in that no higher isolation level
     * allows an action forbidden by a lower one, the standard permits a DBMS to run a transaction
     * at an isolation level stronger than that requested (e.g., a "Read committed" transaction may
     * actually be performed at a "Repeatable read" isolation level).
     *
     * @link http://en.wikipedia.org/wiki/Isolation_(database_systems)
     */
    public const ISOLATION_READ_UNCOMMITTED = 'READ UNCOMMITTED';

    /**
     * Create new driver instance.
     */
    public static function create(DriverConfig $config): self;

    /**
     * Returns {@see true} in the case that the connection is available only
     * for reading or {@see false} instead.
     */
    public function isReadonly(): bool;

    /**
     * Driver type (name).
     *
     * @psalm-return non-empty-string
     */
    public function getType(): string;

    /**
     * Connection specific timezone, at this moment locked to UTC.
     */
    public function getTimezone(): \DateTimeZone;

    public function getSchemaHandler(): HandlerInterface;

    /**
     * Returns query compiler associated with the driver.
     */
    public function getQueryCompiler(): CompilerInterface;

    /**
     * Provides the ability to initiate active queries.
     */
    public function getQueryBuilder(): BuilderInterface;

    /**
     * Force driver connection.
     *
     * @throws DriverException
     */
    public function connect(): void;

    /**
     * Check if driver already connected.
     */
    public function isConnected(): bool;

    /**
     * Disconnect driver.
     */
    public function disconnect(): void;

    /**
     * Quote value.
     *
     * @param int $type Parameter type.
     *
     * @psalm-return non-empty-string
     */
    public function quote(mixed $value, int $type = \PDO::PARAM_STR): string;

    /**
     * Wraps PDO query method with custom representation class.
     *
     * @psalm-param non-empty-string $statement
     *
     * @throws StatementException
     */
    public function query(string $statement, array $parameters = []): StatementInterface;

    /**
     * Execute query and return number of affected rows.
     *
     * @psalm-param non-empty-string $query
     *
     * @throws StatementException
     * @throws ReadonlyConnectionException
     */
    public function execute(string $query, array $parameters = []): int;

    /**
     * Get id of last inserted row, this method must be called after insert query. Attention,
     * such functionality may not work in some DBMS property (Postgres).
     *
     * @param string|null $sequence Name of the sequence object from which the ID should be
     *                              returned.
     *
     * @return mixed
     */
    public function lastInsertID(?string $sequence = null);

    /**
     * Start SQL transaction with specified isolation level (not all DBMS support it). Nested
     * transactions are processed using savepoints.
     *
     * @link   http://en.wikipedia.org/wiki/Database_transaction
     * @link   http://en.wikipedia.org/wiki/Isolation_(database_systems)
     *
     * @return bool True of success.
     */
    public function beginTransaction(?string $isolationLevel = null): bool;

    /**
     * Commit the active database transaction.
     *
     * @return bool True of success.
     */
    public function commitTransaction(): bool;

    /**
     * Rollback the active database transaction.
     *
     * @return bool True of success.
     */
    public function rollbackTransaction(): bool;

    /**
     * Get current opened transaction level.
     */
    public function getTransactionLevel(): int;
}
