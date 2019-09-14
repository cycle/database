<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Database\Driver;

/**
 * Transaction scope provides the ability to store prepared statements within the boundaries of active transaction.
 */
final class TransactionScope
{
    private $level = 0;

    /** @var \PDOStatement[] */
    private $statements = [];

    /**
     * Returns prepared statement or null if not found.
     *
     * @param string $sql
     * @return \PDOStatement|null
     */
    public function getPrepared(string $sql): ?\PDOStatement
    {
        if ($this->level === 0) {
            return null;
        }

        for ($i = 0; $i <= $this->level; $i++) {
            if (isset($this->statements[$i][$sql])) {
                return $this->statements[$i][$sql];
            }
        }

        return null;
    }

    /**
     * Store prepared statement for future re-use. Only inside the transaction.
     *
     * @param string        $sql
     * @param \PDOStatement $statement
     */
    public function setPrepared(string $sql, \PDOStatement $statement)
    {
        if ($this->level === 0) {
            return;
        }

        $this->statements[$this->level][$sql] = $statement;
    }

    /**
     * Returns current transaction level.
     *
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Enter the transaction or savepoint.
     */
    public function open(): void
    {
        $this->level++;
        $this->statements[$this->level] = [];
    }

    /**
     * Leave the transaction or savepoint.
     */
    public function close(): void
    {
        unset($this->statements[$this->level]);
        $this->level--;
    }

    /**
     * Resets the scope to outside of transaction.
     */
    public function reset(): void
    {
        $this->level = 0;
        $this->statements = [];
    }
}
