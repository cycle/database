<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\MySQL\MySQLDriver;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Driver\SQLServer\SQLServerDriver;
use Cycle\Database\Exception\StatementException;
use PHPUnit\Framework\TestCase;

/**
 * Every exception below is a verbatim capture from the server it names: the messages carry the
 * user data the server echoes back, which is what makes classifying on them unsafe.
 */
final class MapExceptionTest extends TestCase
{
    public function postgresProvider(): iterable
    {
        yield 'check violation, uuid holding 0800' => [
            '23514',
            "SQLSTATE[23514]: Check violation: 7 ERROR:  new row for relation \"pd_destruction_log\" violates check constraint \"pd_destruction_log_reason_check\"\nDETAIL:  Failing row contains (f5a31835-fae5-43eb-8efa-cce00b90800a, whim).",
            StatementException\ConstrainException::class,
        ];
        yield 'unique violation, key holding "connection"' => [
            '23505',
            "SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint \"users_email_key\"\nDETAIL:  Key (email)=(no.connection@example.com) already exists.",
            StatementException\ConstrainException::class,
        ];
        yield 'exclusion violation is class 23 but not a number' => [
            '23P01',
            "SQLSTATE[23P01]: Exclusion violation: 7 ERROR:  conflicting key value violates exclusion constraint \"t_r_excl\"\nDETAIL:  Key (r)=([5,15)) conflicts with existing key (r)=([1,10)).",
            StatementException\ConstrainException::class,
        ];
        yield 'not null violation' => [
            '23502',
            'SQLSTATE[23502]: Not null violation: 7 ERROR:  null value in column "value" of relation "test" violates not-null constraint',
            StatementException\ConstrainException::class,
        ];
        yield 'admin shutdown' => [
            '57P01',
            'SQLSTATE[57P01]: Admin shutdown: 7 FATAL:  terminating connection due to administrator command',
            StatementException\ConnectionException::class,
        ];
        yield 'too many connections' => [
            '53300',
            'SQLSTATE[53300]: Too many connections: 7 FATAL:  sorry, too many clients already',
            StatementException\ConnectionException::class,
        ];
        yield 'undefined table whose name contains "connections"' => [
            '42P01',
            'SQLSTATE[42P01]: Undefined table: 7 ERROR:  relation "no_such_connections_table" does not exist',
            StatementException::class,
        ];
        yield 'invalid uuid syntax, value holding 0800' => [
            '22P02',
            'SQLSTATE[22P02]: Invalid text representation: 7 ERROR:  invalid input syntax for type uuid: "f5a31835-0800-nope"',
            StatementException::class,
        ];

        // Connection failures: libpq reports these before a statement exists, so the SQLSTATE lives
        // in errorInfo while getCode() holds libpq's own connection status.
        yield 'connection refused' => [
            '08006',
            'SQLSTATE[08006] [7] connection to server at "127.0.0.1", port 15499 failed: Connection refused',
            StatementException\ConnectionException::class,
            7,
        ];
        yield 'backend terminated under an open session' => [
            'HY000',
            "SQLSTATE[HY000]: General error: 7 FATAL:  terminating connection due to administrator command\nserver closed the connection unexpectedly",
            StatementException\ConnectionException::class,
        ];
        yield 'socket already gone' => [
            'HY000',
            'SQLSTATE[HY000]: General error: 7 no connection to the server',
            StatementException\ConnectionException::class,
        ];
        yield 'eof detected' => [
            'HY000',
            'SQLSTATE[HY000]: General error: 7 EOF detected',
            StatementException\ConnectionException::class,
        ];
    }

    public function sqlServerProvider(): iterable
    {
        yield 'primary key violation printing the duplicate uuid' => [
            '23000',
            "SQLSTATE[23000]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Violation of PRIMARY KEY constraint 'PK__t268__3213E83F'. Cannot insert duplicate key in object 'dbo.t268'. The duplicate key value is (f5a31835-fae5-43eb-8efa-cce00b90800a).",
            StatementException\ConstrainException::class,
        ];
        yield 'check violation naming a table called connections' => [
            '23000',
            "SQLSTATE[23000]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]The INSERT statement conflicted with the CHECK constraint \"CK__connections__state\". The conflict occurred in database \"master\", table \"dbo.connections\", column 'state'.",
            StatementException\ConstrainException::class,
        ];
        yield 'invalid object name containing "connections"' => [
            '42S02',
            "SQLSTATE[42S02]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]Invalid object name 'no_such_connections_table'.",
            StatementException::class,
        ];

        // The ODBC driver translates its own strings, so on a localized install the SQLSTATE is the
        // only part of these that any needle could match.
        yield 'connection refused, localized' => [
            '08001',
            'SQLSTATE[08001]: [Microsoft][ODBC Driver 18 for SQL Server]Поставщик TCP: Подключение не установлено, т.к. конечный компьютер отверг запрос на подключение.',
            StatementException\ConnectionException::class,
        ];
        yield 'link dropped, localized' => [
            '08S01',
            'SQLSTATE[08S01]: [Microsoft][ODBC Driver 18 for SQL Server]Поставщик TCP: Удаленный хост принудительно разорвал существующее подключение.',
            StatementException\ConnectionException::class,
        ];
    }

    public function mySQLProvider(): iterable
    {
        yield 'duplicate entry printing a uuid holding 0800' => [
            '23000',
            "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'f5a31835-fae5-43eb-8efa-cce00b90800a' for key 't268.PRIMARY'",
            StatementException\ConstrainException::class,
            null,
            1062,
        ];
        // mysql files these under HY000; see MySQLDriver::CONSTRAINT_ERRNOS.
        yield 'check constraint violated' => [
            'HY000',
            "SQLSTATE[HY000]: General error: 3819 Check constraint 'c_pos' is violated.",
            StatementException\ConstrainException::class,
            null,
            3819,
        ];
        yield 'required column left out of the statement' => [
            'HY000',
            "SQLSTATE[HY000]: General error: 1364 Field 'nn' doesn't have a default value",
            StatementException\ConstrainException::class,
            null,
            1364,
        ];
        yield 'duplicate check constraint name is a DDL error, not a violation' => [
            'HY000',
            "SQLSTATE[HY000]: General error: 3822 Duplicate check constraint name 'c_pos'.",
            StatementException::class,
            null,
            3822,
        ];
        yield 'incorrect integer value is a coercion failure, not a violation' => [
            'HY000',
            "SQLSTATE[HY000]: General error: 1366 Incorrect integer value: 'zz' for column 'id' at row 1",
            StatementException::class,
            null,
            1366,
        ];
        yield 'table not found, name contains "connections"' => [
            '42S02',
            "SQLSTATE[42S02]: Base table or view not found: 1146 Table 'spiral.no_such_connections_table' doesn't exist",
            StatementException::class,
            null,
            1146,
        ];
        yield 'access denied' => [
            'HY000',
            "SQLSTATE[HY000] [1045] Access denied for user 'root'@'172.18.0.1' (using password: YES)",
            StatementException::class,
            1045,
            1045,
        ];
        yield 'server has gone away' => [
            'HY000',
            'SQLSTATE[HY000]: General error: 2006 MySQL server has gone away',
            StatementException\ConnectionException::class,
            null,
            2006,
        ];
        yield 'connection refused, localized' => [
            'HY000',
            'SQLSTATE[HY000] [2002] Подключение не установлено, т.к. конечный компьютер отверг запрос на подключение',
            StatementException\ConnectionException::class,
            2002,
            2002,
        ];
    }

    public function sqLiteProvider(): iterable
    {
        yield 'unique constraint' => [
            '23000',
            'SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: t268.id',
            StatementException\ConstrainException::class,
            null,
            19,
        ];
        yield 'no such table containing "connections"' => [
            'HY000',
            'SQLSTATE[HY000]: General error: 1 no such table: no_such_connections_table',
            StatementException::class,
            null,
            1,
        ];
    }

    /**
     * @dataProvider postgresProvider
     */
    public function testPostgres(
        string $sqlState,
        string $message,
        string $expected,
        int|string|null $code = null,
        ?int $driverCode = null,
    ): void {
        $this->assertMapsTo(PostgresDriver::class, $sqlState, $message, $expected, $code, $driverCode);
    }

    /**
     * @dataProvider sqlServerProvider
     */
    public function testSQLServer(
        string $sqlState,
        string $message,
        string $expected,
        int|string|null $code = null,
        ?int $driverCode = null,
    ): void {
        $this->assertMapsTo(SQLServerDriver::class, $sqlState, $message, $expected, $code, $driverCode);
    }

    /**
     * @dataProvider mySQLProvider
     */
    public function testMySQL(
        string $sqlState,
        string $message,
        string $expected,
        int|string|null $code = null,
        ?int $driverCode = null,
    ): void {
        $this->assertMapsTo(MySQLDriver::class, $sqlState, $message, $expected, $code, $driverCode);
    }

    /**
     * @dataProvider sqLiteProvider
     */
    public function testSQLite(
        string $sqlState,
        string $message,
        string $expected,
        int|string|null $code = null,
        ?int $driverCode = null,
    ): void {
        $this->assertMapsTo(SQLiteDriver::class, $sqlState, $message, $expected, $code, $driverCode);
    }

    public function testSqlStateIsReadFromTheMessageWhenErrorInfoIsMissing(): void
    {
        $exception = new \PDOException('SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key');

        self::assertInstanceOf(
            StatementException\ConstrainException::class,
            $this->map(PostgresDriver::class, $exception),
        );
    }

    public function testExceptionWithoutAnySqlStateFallsBackToTheMessage(): void
    {
        self::assertInstanceOf(
            StatementException\ConnectionException::class,
            $this->map(PostgresDriver::class, new \RuntimeException('Broken pipe')),
        );

        self::assertNotInstanceOf(
            StatementException\ConnectionException::class,
            $this->map(PostgresDriver::class, new \RuntimeException('Something else entirely')),
        );
    }

    /**
     * @param class-string<Driver> $driver
     * @param class-string<StatementException> $expected
     */
    private function assertMapsTo(
        string $driver,
        string $sqlState,
        string $message,
        string $expected,
        int|string|null $code,
        ?int $driverCode,
    ): void {
        $exception = new \PDOException($message);
        $exception->errorInfo = [$sqlState, $driverCode, $message];
        $this->setCode($exception, $code ?? $sqlState);

        $mapped = $this->map($driver, $exception);

        self::assertInstanceOf($expected, $mapped);
        // ConstrainException and ConnectionException both extend StatementException, so an
        // assertInstanceOf against the base class alone would pass for either of them.
        self::assertSame($expected, $mapped::class);
    }

    /**
     * @param class-string<Driver> $driver
     */
    private function map(string $driver, \Throwable $exception): StatementException
    {
        $method = new \ReflectionMethod($driver, 'mapException');

        return $method->invoke(
            (new \ReflectionClass($driver))->newInstanceWithoutConstructor(),
            $exception,
            'SELECT 1',
        );
    }

    /**
     * PDO assigns the SQLSTATE string to a property typed as int, which no constructor accepts.
     */
    private function setCode(\PDOException $exception, int|string $code): void
    {
        (new \ReflectionProperty(\Exception::class, 'code'))->setValue($exception, $code);
    }
}
