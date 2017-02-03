<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Drivers\SQLite;

use Psr\Log\LoggerInterface;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\Drivers\SQLite\Schemas\SQLiteTable;
use Spiral\Database\Entities\AbstractHandler;
use Spiral\Database\Entities\Driver;
use Spiral\Database\Exceptions\DriverException;

/**
 * Talks to sqlite databases.
 */
class SQLiteDriver extends Driver
{
    /**
     * Driver type.
     */
    const TYPE = DatabaseInterface::SQLITE;

    /**
     * Driver schemas.
     */
    const TABLE_SCHEMA_CLASS = SQLiteTable::class;

    /**
     * Query compiler class.
     */
    const QUERY_COMPILER = SQLiteCompiler::class;

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
        return substr($this->options['connection'], 7);
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
    public function truncateData(string $table)
    {
        $this->statement("DELETE FROM {$this->identifier($table)}");
    }

    /**
     * {@inheritdoc}
     */
    public function tableNames(): array
    {
        $tables = [];
        foreach ($this->query("SELECT name FROM 'sqlite_master' WHERE type = 'table'") as $table) {
            if ($table['name'] != 'sqlite_sequence') {
                $tables[] = $table['name'];
            }
        }

        return $tables;
    }

    /**
     * {@inheritdoc}
     */
    protected function isolationLevel(string $level)
    {
        if ($this->isProfiling()) {
            $this->logger()->alert(
                "Transaction isolation level is not fully supported by SQLite ({$level})."
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler(LoggerInterface $logger = null): AbstractHandler
    {
        return new SQLiteHandler($this, $logger);
    }
}
