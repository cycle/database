<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Query;

use Cycle\Database\Config\Postgres\TcpConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Database;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Query\SelectQuery;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class SelectQueryTest
{
    private Database $db;

    #[BeforeTest]
    public function fill(): void
    {
        $this->db = new Database('default', '', SQLiteDriver::create(new SQLiteDriverConfig()));
        $this->db->execute('CREATE TABLE t (a INTEGER)');
        $this->db->execute('INSERT INTO t VALUES (1), (2), (3)');
    }

    public function countLeavesTheQueryUntouched(): void
    {
        $query = $this->query();

        Assert::same($query->count(), 3);
        Assert::same($query->sqlStatement(), $this->query()->sqlStatement());
    }

    public function aggregatesLeaveTheQueryUntouched(): void
    {
        $query = $this->query();

        Assert::equals($query->sum('a'), 6);
        Assert::same($query->sqlStatement(), $this->query()->sqlStatement());
    }

    public function runningInChunksLeavesTheQueryUntouched(): void
    {
        $query = $this->query();
        $chunks = [];

        $query->runChunks(2, static function ($rows) use (&$chunks): void {
            $chunks[] = \array_column(\iterator_to_array($rows), 'a');
        });

        Assert::same($chunks, [[1, 2], [3]]);
        Assert::same($query->sqlStatement(), $this->query()->sqlStatement());
    }

    public function locksTheSelectedRows(): void
    {
        $db = new Database('default', '', PostgresDriver::create(new PostgresDriverConfig(new TcpConnectionConfig('db'))));

        $sql = $db->select()->from('t')->forUpdate()->sqlStatement();

        Assert::same($sql, "SELECT *\nFROM \"t\" FOR UPDATE");
    }

    private function query(): SelectQuery
    {
        return $this->db->select('a')->from('t')->orderBy('a');
    }
}
