<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Driver\Traits;

use PDO;
use Psr\Log\LoggerInterface;
use Spiral\Database\Exception\ConnectionException;

trait TransactionTrait
{
    /**
     * Transaction level (count of nested transactions). Not all drives can support nested
     * transactions.
     *
     * @var int
     */
    private $transactionLevel = 0;

    /**
     * Start SQL transaction with specified isolation level (not all DBMS support it). Nested
     * transactions are processed using savepoints.
     *
     * @link http://en.wikipedia.org/wiki/Database_transaction
     * @link http://en.wikipedia.org/wiki/Isolation_(database_systems)
     *
     * @param string $isolationLevel
     * @return bool
     */
    public function beginTransaction(string $isolationLevel = null): bool
    {
        ++$this->transactionLevel;

        if ($this->transactionLevel == 1) {
            if (!empty($isolationLevel)) {
                $this->isolationLevel($isolationLevel);
            }

            $this->isProfiling() && $this->getLogger()->info('Begin transaction');

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
            $this->isProfiling() && $this->getLogger()->info('Commit transaction');

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
            $this->isProfiling() && $this->getLogger()->info('Rollback transaction');

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
     * Set transaction isolation level, this feature may not be supported by specific database
     * driver.
     *
     * @param string $level
     */
    protected function isolationLevel(string $level)
    {
        if (!empty($level)) {
            $this->isProfiling() && $this->getLogger()->info("Set transaction isolation level to '{$level}'");
            $this->execute("SET TRANSACTION ISOLATION LEVEL {$level}");
        }
    }

    /**
     * Create nested transaction save point.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param string $name Savepoint name/id, must not contain spaces and be valid database identifier.
     */
    protected function savepointCreate(string $name)
    {
        $this->isProfiling() && $this->getLogger()->info("Transaction: new savepoint 'SVP{$name}'");
        $this->execute('SAVEPOINT ' . $this->identifier("SVP{$name}"));
    }

    /**
     * Commit/release savepoint.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param string $name Savepoint name/id, must not contain spaces and be valid database identifier.
     */
    protected function savepointRelease(string $name)
    {
        $this->isProfiling() && $this->getLogger()->info("Transaction: release savepoint 'SVP{$name}'");
        $this->execute('RELEASE SAVEPOINT ' . $this->identifier("SVP{$name}"));
    }

    /**
     * Rollback savepoint.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param string $name Savepoint name/id, must not contain spaces and be valid database identifier.
     */
    protected function savepointRollback(string $name)
    {
        $this->isProfiling() && $this->getLogger()->info("Transaction: rollback savepoint 'SVP{$name}'");
        $this->execute('ROLLBACK TO SAVEPOINT ' . $this->identifier("SVP{$name}"));
    }

    /**
     * Driver specific database/table identifier quotation.
     *
     * @param string $identifier
     * @return string
     */
    abstract public function identifier(string $identifier): string;

    /**
     * Execute query and return number of affected rows.
     *
     * @param string $query
     * @param array  $parameters
     * @return int
     */
    abstract public function execute(string $query, array $parameters = []): int;

    /**
     * Disconnect driver.
     */
    abstract public function disconnect();

    /**
     * Attempt to reconnect driver.
     */
    abstract public function reconnect();

    /**
     * Check if profiling mode is enabled.
     *
     * @return bool
     */
    abstract public function isProfiling(): bool;

    /**
     * Get associated PDO connection. Will automatically connect if such connection does not exists.
     *
     * @return PDO
     */
    abstract protected function getPDO(): PDO;

    /**
     * @return LoggerInterface
     */
    abstract protected function getLogger(): LoggerInterface;
}