<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Query;

use Cycle\Database\Config\Postgres\TcpConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Database\Driver\Postgres\Query\PostgresInsertQuery;
use Cycle\Database\Driver\SQLite\Query\SQLiteInsertQuery;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\SubQuery;
use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\Query\UpdateQuery;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class QueryStateTest
{
    public function takesTheInitialColumnsOfASelect(): void
    {
        $query = $this->sqlite(new SelectQuery(['t'], ['a', 'b']));

        Assert::same($query->sqlStatement(), "SELECT \"a\", \"b\"\nFROM \"t\"");
    }

    public function returnsTheLimitAndOffset(): void
    {
        $query = (new SelectQuery())->limit(10)->offset(20);

        Assert::same($query->getLimit(), 10);
        Assert::same($query->getOffset(), 20);
    }

    public function takesTheInitialConditionsOfAnUpdate(): void
    {
        $query = $this->sqlite(new UpdateQuery('t', ['b' => 2], ['a' => 1]));

        Assert::same($query->sqlStatement(), "UPDATE \"t\"\nSET \"a\" = ?\nWHERE \"b\" = ? ");
    }

    public function takesTheInitialConditionsOfADelete(): void
    {
        $query = $this->sqlite(new DeleteQuery('t', ['b' => 2]));

        Assert::same($query->sqlStatement(), "DELETE FROM \"t\"\nWHERE \"b\" = ? ");
    }

    public function describesACompilationErrorForDebugging(): void
    {
        $info = (new SelectQuery())->__debugInfo();

        Assert::string($info['queryString'])->startsWith('[ERROR: ');
    }

    public function returnsTheQueryOfASubQuery(): void
    {
        $query = $this->sqlite(new SelectQuery(['t']));

        Assert::same((new SubQuery($query, 'x'))->getQuery(), $query);
    }

    public function bindsTheSQLiteInsertOnlyToSQLite(): never
    {
        Expect::exception(BuilderException::class)->withMessage('SQLite InsertQuery can be used only with SQLite driver');

        (new SQLiteInsertQuery())->withDriver($this->postgresDriver());
    }

    public function bindsThePostgresInsertOnlyToPostgres(): never
    {
        Expect::exception(BuilderException::class)->withMessage('Postgres InsertQuery can be used only with Postgres driver');

        (new PostgresInsertQuery())->withDriver(SQLiteDriver::create(new SQLiteDriverConfig()));
    }

    /**
     * @template T of SelectQuery|UpdateQuery|DeleteQuery
     *
     * @param T $query
     *
     * @return T
     */
    private function sqlite(SelectQuery|UpdateQuery|DeleteQuery $query): SelectQuery|UpdateQuery|DeleteQuery
    {
        return $query->withDriver(SQLiteDriver::create(new SQLiteDriverConfig(queryCache: false)), '');
    }

    private function postgresDriver(): PostgresDriver
    {
        return PostgresDriver::create(new PostgresDriverConfig(new TcpConnectionConfig('db')));
    }
}
