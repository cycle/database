<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Entity;

use Spiral\Core\Container\InjectableInterface;
use Spiral\Database\Builder\DeleteQuery;
use Spiral\Database\Builder\InsertQuery;
use Spiral\Database\Builder\SelectQuery;
use Spiral\Database\Builder\UpdateQuery;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Exception\DriverException;
use Spiral\Database\Exception\QueryException;

/**
 * Database class is high level abstraction at top of Driver. Databases usually linked to real
 * database or logical portion of database (filtered by prefix).
 */
class Database implements DatabaseInterface, InjectableInterface
{
    /**
     * This is magick constant used by Spiral Container, it helps system to resolve controllable
     * injections.
     */
    const INJECTOR = DatabaseManager::class;

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
    const ISOLATION_SERIALIZABLE = 'SERIALIZABLE';

    /**
     * Transaction isolation level 'REPEATABLE READ'.
     *
     * In this isolation level, a lock-based concurrency control DBMS implementation keeps read and
     * write locks (acquired on selected data) until the end of the transaction. However,
     * range-locks are not managed, so phantom reads can occur.
     *
     * @link http://en.wikipedia.org/wiki/Isolation_(database_systems)
     */
    const ISOLATION_REPEATABLE_READ = 'REPEATABLE READ';

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
    const ISOLATION_READ_COMMITTED = 'READ COMMITTED';

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
    const ISOLATION_READ_UNCOMMITTED = 'READ UNCOMMITTED';

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var string
     */
    private $prefix = '';

    /**
     * @var Driver
     */
    private $driver = null;

    /**
     * @param Driver $driver Driver instance responsible for database connection.
     * @param string $name   Internal database name/id.
     * @param string $prefix Default database table prefix, will be used for all table
     *                       identifiers.
     */
    public function __construct(Driver $driver, string $name, string $prefix = '')
    {
        $this->driver = $driver;
        $this->name = $name;
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return $this->driver->getType();
    }

    /**
     * @return Driver
     */
    public function getDriver(): Driver
    {
        return $this->driver;
    }

    /**
     * Update database prefix.
     *
     * @todo immutable?
     *
     * @param string $prefix
     *
     * @return self
     */
    public function setPrefix(string $prefix): Database
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(string $query, array $parameters = []): int
    {
        return $this->driver->statement($query, $parameters)->rowCount();
    }

    /**
     * Prepare PDO statement.
     *
     * @param string $query
     *
     * @return \PDOStatement
     *
     * @throws DriverException
     * @throws QueryException
     */
    public function prepare(string $query): \PDOStatement
    {
        return $this->driver->prepare($query);
    }

    /**
     * {@inheritdoc}
     *
     * @return QueryStatement
     */
    public function query(string $query, array $parameters = []): QueryStatement
    {
        return $this->driver->query($query, $parameters);
    }

    /**
     * Get instance of InsertBuilder associated with current Database.
     *
     * @param string $table Table where values should be inserted to.
     *
     * @return InsertQuery
     */
    public function insert(string $table = ''): InsertQuery
    {
        return $this->driver->insertBuilder($this->prefix, compact('table'));
    }

    /**
     * Get instance of UpdateBuilder associated with current Database.
     *
     * @param string $table  Table where rows should be updated in.
     * @param array  $values Initial set of columns to update associated with their values.
     * @param array  $where  Initial set of where rules specified as array.
     *
     * @return UpdateQuery
     */
    public function update(string $table = '', array $values = [], array $where = []): UpdateQuery
    {
        return $this->driver->updateBuilder($this->prefix, compact('table', 'where', 'values'));
    }

    /**
     * Get instance of DeleteBuilder associated with current Database.
     *
     * @param string $table Table where rows should be deleted from.
     * @param array  $where Initial set of where rules specified as array.
     *
     * @return DeleteQuery
     */
    public function delete(string $table = '', array $where = []): DeleteQuery
    {
        return $this->driver->deleteBuilder($this->prefix, compact('table', 'where'));
    }

    /**
     * Get instance of SelectBuilder associated with current Database.
     *
     * @param array|string $columns Columns to select.
     *
     * @return SelectQuery
     */
    public function select($columns = '*'): SelectQuery
    {
        $columns = func_get_args();
        if (is_array($columns) && isset($columns[0]) && is_array($columns[0])) {
            //Can be required in some cases while collecting data from Table->select(), stupid bug.
            $columns = $columns[0];
        }

        return $this->driver->selectBuilder($this->prefix, ['columns' => $columns]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $isolationLevel
     *
     * @throws \Exception
     */
    public function transaction(callable $callback, string $isolationLevel = null)
    {
        $this->begin($isolationLevel);

        try {
            $result = call_user_func($callback, $this);
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @link http://en.wikipedia.org/wiki/Isolation_(database_systems)
     *
     * @param string $isolationLevel
     */
    public function begin(string $isolationLevel = null)
    {
        return $this->driver->beginTransaction($isolationLevel);
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return $this->driver->commitTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        return $this->driver->rollbackTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable(string $name): bool
    {
        return $this->driver->hasTable($this->prefix . $name);
    }

    /**
     * {@inheritdoc}
     *
     * @return Table
     */
    public function table(string $name): Table
    {
        return new Table($this, $name);
    }

    /**
     * {@inheritdoc}
     *
     * @return Table[]
     */
    public function getTables(): array
    {
        $result = [];
        foreach ($this->driver->tableNames() as $table) {
            if ($this->prefix && strpos($table, $this->prefix) !== 0) {
                //Logical partitioning
                continue;
            }

            $result[] = $this->table(substr($table, strlen($this->prefix)));
        }

        return $result;
    }

    /**
     * Shortcut to get table abstraction.
     *
     * @param string $name Table name without prefix.
     *
     * @return Table
     */
    public function __get(string $name): Table
    {
        return $this->table($name);
    }
}
