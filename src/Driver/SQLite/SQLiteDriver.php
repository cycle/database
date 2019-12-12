<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\SQLite;

use Spiral\Database\DatabaseInterface;
use Spiral\Database\Driver\Driver;
use Spiral\Database\Driver\HandlerInterface;
use Spiral\Database\Driver\SQLite\Schema\SQLiteTable;
use Spiral\Database\Exception\DriverException;
use Spiral\Database\Exception\StatementException;

/**
 * Talks to sqlite databases.
 */
class SQLiteDriver extends Driver
{
    protected const TYPE               = DatabaseInterface::SQLITE;
    protected const TABLE_SCHEMA_CLASS = SQLiteTable::class;
    protected const QUERY_COMPILER     = SQLiteCompiler::class;

    /**
     * Get driver source database or file name.
     *
     * @return string
     *
     * @throws DriverException
     */
    public function getSource(): string
    {
        //Remove "sqlite:"
        return substr($this->options['connection'] ?? $this->options['dsn'] ?? $this->options['addr'], 7);
    }

    /**
     * {@inheritdoc}
     */
    public function tableNames(): array
    {
        $tables = [];
        foreach ($this->query("SELECT name FROM 'sqlite_master' WHERE type = 'table'") as $table) {
            if ($table['name'] !== 'sqlite_sequence') {
                $tables[] = $table['name'];
            }
        }

        return $tables;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable(string $name): bool
    {
        $query = "SELECT COUNT('sql') FROM 'sqlite_master' WHERE type = 'table' and name = ?";

        return (bool)$this->query($query, [$name])->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function eraseData(string $table): void
    {
        $this->execute("DELETE FROM {$this->identifier($table)}");
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler(): HandlerInterface
    {
        return new SQLiteHandler($this);
    }

    /**
     * {@inheritdoc}
     */
    protected function setIsolationLevel(string $level): void
    {
        $this->getLogger()->alert("Transaction isolation level is not fully supported by SQLite ({$level}).");
    }

    /**
     * {@inheritdoc}
     */
    protected function mapException(\Throwable $exception, string $query): StatementException
    {
        if ((int)$exception->getCode() === 23000) {
            return new StatementException\ConstrainException($exception, $query);
        }

        return new StatementException($exception, $query);
    }
}
