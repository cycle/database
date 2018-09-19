<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Entity;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Spiral\Core\Container;
use Spiral\Core\FactoryInterface;
use Spiral\Database\Query\DeleteQuery;
use Spiral\Database\Query\InsertQuery;
use Spiral\Database\Query\SelectQuery;
use Spiral\Database\Query\UpdateQuery;
use Spiral\Database\Exception\ConnectionException;
use Spiral\Database\Schema\Prototypes\AbstractTable;

/**
 * Driver abstraction is responsible for DBMS specific set of functions and used by Databases to
 * hide implementation specific functionality. Extends PDODriver and adds ability to create driver
 * specific query builders and schemas (basically operates like a factory).
 */
abstract class Driver extends PDODriver
{
    /**
     * Schema table class.
     */
    const TABLE_SCHEMA_CLASS = '';

    /**
     * Commander used to execute commands. :).
     */
    const COMMANDER = '';

    /**
     * Query compiler class.
     */
    const QUERY_COMPILER = '';

    /**
     * Transaction level (count of nested transactions). Not all drives can support nested
     * transactions.
     *
     * @var int
     */
    private $transactionLevel = 0;

    /**
     * Defines IoC scope for all driver specific builders.
     *
     * @var ContainerInterface
     */
    protected $container = null;

    /**
     * @param string             $name
     * @param array              $options
     * @param ContainerInterface $container Required to build instances of query builders and
     *                                      compilers. Also provides support for scope specific
     *                                      functionality like magic paginators and logs (yes, you
     *                                      can store LogsInterface in this container).
     */
    public function __construct(string $name, array $options, ContainerInterface $container = null)
    {
        parent::__construct($name, $options);

        //Factory with default fallback
        $this->container = $container ?? new Container();
    }

    /**
     * Check if table exists.
     *
     * @param string $name
     *
     * @return bool
     */
    abstract public function hasTable(string $name): bool;

    /**
     * Clean (truncate) specified driver table.
     *
     * @param string $table Table name with prefix included.
     */
    abstract public function truncateData(string $table);

    /**
     * Get every available table name as array.
     *
     * @return array
     */
    abstract public function tableNames(): array;

    /**
     * Get Driver specific AbstractTable implementation.
     *
     * @param string $table  Table name without prefix included.
     * @param string $prefix Database specific table prefix, this parameter is not required,
     *                       but if provided all
     *                       foreign keys will be created using it.
     *
     * @return AbstractTable
     */
    public function tableSchema(string $table, string $prefix = ''): AbstractTable
    {
        return $this->getFactory()->make(
            static::TABLE_SCHEMA_CLASS,
            ['driver' => $this, 'name' => $table, 'prefix' => $prefix]
        );
    }

    /**
     * Get instance of Driver specific QueryCompiler.
     *
     * @param string $prefix Database specific table prefix, used to quote table names and build
     *                       aliases.
     *
     * @return QueryCompiler
     */
    public function queryCompiler(string $prefix = ''): QueryCompiler
    {
        return $this->getFactory()->make(
            static::QUERY_COMPILER,
            ['driver' => $this, 'quoter' => new Quoter($this, $prefix)]
        );
    }

    /**
     * Get InsertQuery builder with driver specific query compiler.
     *
     * @param string $prefix     Database specific table prefix, used to quote table names and build
     *                           aliases.
     * @param array  $parameters Initial builder parameters.
     *
     * @return InsertQuery
     */
    public function insertBuilder(string $prefix, array $parameters = []): InsertQuery
    {
        return $this->getFactory()->make(
            InsertQuery::class,
            ['driver' => $this, 'compiler' => $this->queryCompiler($prefix)] + $parameters
        );
    }

    /**
     * Get SelectQuery builder with driver specific query compiler.
     *
     * @param string $prefix     Database specific table prefix, used to quote table names and build
     *                           aliases.
     * @param array  $parameters Initial builder parameters.
     *
     * @return SelectQuery
     */
    public function selectBuilder(string $prefix, array $parameters = []): SelectQuery
    {
        return $this->getFactory()->make(
            SelectQuery::class,
            ['driver' => $this, 'compiler' => $this->queryCompiler($prefix)] + $parameters
        );
    }

    /**
     * Get DeleteQuery builder with driver specific query compiler.
     *
     * @param string $prefix     Database specific table prefix, used to quote table names and build
     *                           aliases.
     * @param array  $parameters Initial builder parameters.
     *
     * @return DeleteQuery
     */
    public function deleteBuilder(string $prefix, array $parameters = []): DeleteQuery
    {
        return $this->getFactory()->make(
            DeleteQuery::class,
            ['driver' => $this, 'compiler' => $this->queryCompiler($prefix)] + $parameters
        );
    }

    /**
     * Get UpdateQuery builder with driver specific query compiler.
     *
     * @param string $prefix     Database specific table prefix, used to quote table names and build
     *                           aliases.
     * @param array  $parameters Initial builder parameters.
     *
     * @return UpdateQuery
     */
    public function updateBuilder(string $prefix, array $parameters = []): UpdateQuery
    {
        return $this->getFactory()->make(
            UpdateQuery::class,
            ['driver' => $this, 'compiler' => $this->queryCompiler($prefix)] + $parameters
        );
    }

    /**
     * Handler responsible for schema related operations. Handlers responsible for sync flow of
     * tables and columns, provide logger to aggregate all logger operations.
     *
     * @param LoggerInterface $logger
     *
     * @return AbstractHandler
     */
    abstract public function getHandler(LoggerInterface $logger = null): AbstractHandler;

    /**
     * Start SQL transaction with specified isolation level (not all DBMS support it). Nested
     * transactions are processed using savepoints.
     *
     * @link   http://en.wikipedia.org/wiki/Database_transaction
     * @link   http://en.wikipedia.org/wiki/Isolation_(database_systems)
     *
     * @param string $isolationLevel
     *
     * @return bool
     */
    public function beginTransaction(string $isolationLevel = null): bool
    {
        ++$this->transactionLevel;

        if ($this->transactionLevel == 1) {
            if (!empty($isolationLevel)) {
                $this->isolationLevel($isolationLevel);
            }

            if ($this->isProfiling()) {
                $this->logger()->info('Begin transaction');
            }

            try {
                return $this->getPDO()->beginTransaction();
            } catch (ConnectionException $e) {
                $this->reconnect();

                return $this->getPDO()->beginTransaction();
            }
        }

        $this->savepointCreate($this->transactionLevel);

        return true;
    }

    /**
     * Commit the active database transaction.
     *
     * @return bool
     */
    public function commitTransaction(): bool
    {
        --$this->transactionLevel;

        if ($this->transactionLevel == 0) {
            if ($this->isProfiling()) {
                $this->logger()->info('Commit transaction');
            }
            try {
                return $this->getPDO()->commit();
            } catch (ConnectionException $e) {
                $this->reconnect();

                return $this->getPDO()->commit();
            }
        }

        $this->savepointRelease($this->transactionLevel + 1);

        return true;
    }

    /**
     * Rollback the active database transaction.
     *
     * @return bool
     */
    public function rollbackTransaction(): bool
    {
        --$this->transactionLevel;

        if ($this->transactionLevel == 0) {
            if ($this->isProfiling()) {
                $this->logger()->info('Rollback transaction');
            }
            try {
                return $this->getPDO()->rollBack();
            } catch (ConnectionException $e) {
                $this->reconnect();

                return $this->getPDO()->rollBack();
            }
        }

        $this->savepointRollback($this->transactionLevel + 1);

        return true;
    }

    /**
     * Get driver specific factory.
     *
     * @return FactoryInterface
     */
    protected function getFactory(): FactoryInterface
    {
        if ($this->container instanceof FactoryInterface) {
            return $this->container;
        }

        return $this->container->get(FactoryInterface::class);
    }

    /**
     * Set transaction isolation level, this feature may not be supported by specific database
     * driver.
     *
     * @param string $level
     */
    protected function isolationLevel(string $level)
    {
        if ($this->isProfiling()) {
            $this->logger()->info("Set transaction isolation level to '{$level}'");
        }

        if (!empty($level)) {
            $this->statement("SET TRANSACTION ISOLATION LEVEL {$level}");
        }
    }

    /**
     * Create nested transaction save point.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param string $name Savepoint name/id, must not contain spaces and be valid database
     *                     identifier.
     */
    protected function savepointCreate(string $name)
    {
        if ($this->isProfiling()) {
            $this->logger()->info("Transaction: new savepoint 'SVP{$name}'");
        }

        $this->statement('SAVEPOINT ' . $this->identifier("SVP{$name}"));
    }

    /**
     * Commit/release savepoint.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param string $name Savepoint name/id, must not contain spaces and be valid database
     *                     identifier.
     */
    protected function savepointRelease(string $name)
    {
        if ($this->isProfiling()) {
            $this->logger()->info("Transaction: release savepoint 'SVP{$name}'");
        }

        $this->statement('RELEASE SAVEPOINT ' . $this->identifier("SVP{$name}"));
    }

    /**
     * Rollback savepoint.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param string $name Savepoint name/id, must not contain spaces and be valid database
     *                     identifier.
     */
    protected function savepointRollback(string $name)
    {
        if ($this->isProfiling()) {
            $this->logger()->info("Transaction: rollback savepoint 'SVP{$name}'");
        }

        $this->statement('ROLLBACK TO SAVEPOINT ' . $this->identifier("SVP{$name}"));
    }
}
