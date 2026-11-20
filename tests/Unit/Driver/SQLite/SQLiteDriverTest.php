<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\SQLite;

use Cycle\Database\Config\MySQL\ConnectionConfig as MySQLConnectionConfig;
use Cycle\Database\Config\MySQL\TcpConnectionConfig;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Driver\CompilerCache;
use Cycle\Database\Driver\MySQL\MySQLDriver;
use Cycle\Database\Driver\SQLite\SQLiteCompiler;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\Query\UpdateQuery;
use Psr\Log\AbstractLogger;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class SQLiteDriverTest
{
    public function wrapsTheCompilerInACacheOnlyWhenTheQueryCacheIsOn(): void
    {
        $cached = SQLiteDriver::create(new SQLiteDriverConfig(queryCache: true));
        $plain = SQLiteDriver::create(new SQLiteDriverConfig(queryCache: false));

        Assert::instanceOf($cached->getQueryCompiler(), CompilerCache::class);
        Assert::instanceOf($plain->getQueryCompiler(), SQLiteCompiler::class);
    }

    public function readsTheSourceFromTheConnectionWhenItProvidesOne(): void
    {
        $mysql = MySQLDriver::create(new MySQLDriverConfig(new TcpConnectionConfig('shop', 'db.local')));
        $anonymous = MySQLDriver::create(new MySQLDriverConfig(new class extends MySQLConnectionConfig {
            public function getDsn(): string
            {
                return 'mysql:host=db.local';
            }
        }));

        Assert::same($mysql->getSource(), 'shop');
        Assert::same($anonymous->getSource(), '*');
    }

    public function connectingAgainKeepsTheOpenConnection(): void
    {
        $driver = SQLiteDriver::create(new SQLiteDriverConfig());
        $driver->execute('CREATE TABLE t (id INTEGER)');

        $driver->connect();

        Assert::same($driver->query('SELECT COUNT(*) FROM t')->fetchColumn(), 0);
    }

    public function disconnectingDropsStatementsPreparedOnTheOldConnection(): void
    {
        $driver = SQLiteDriver::create(new SQLiteDriverConfig());
        $driver->execute('CREATE TABLE t (id INTEGER)');
        $driver->query('SELECT * FROM t');

        $driver->disconnect();

        Expect::exception(StatementException::class)->withMessageContaining('no such table');
        $driver->query('SELECT * FROM t');
    }

    public function disconnectingResetsTheTransactionLevel(): void
    {
        $driver = SQLiteDriver::create(new SQLiteDriverConfig());
        $driver->beginTransaction();

        $driver->disconnect();

        Assert::same($driver->getTransactionLevel(), 0);
    }

    public function nestedTransactionsWorkWithoutALogger(): void
    {
        $driver = SQLiteDriver::create(new SQLiteDriverConfig());
        $driver->execute('CREATE TABLE t (id INTEGER)');

        Assert::true($driver->beginTransaction());
        Assert::true($driver->beginTransaction());
        $driver->execute('INSERT INTO t VALUES (1)');
        Assert::true($driver->rollbackTransaction());
        Assert::true($driver->beginTransaction());
        $driver->execute('INSERT INTO t VALUES (2)');
        Assert::true($driver->commitTransaction());
        Assert::true($driver->commitTransaction());

        Assert::same($driver->query('SELECT id FROM t')->fetchAll(), [['id' => 2]]);
        Assert::same($driver->lastInsertID(), '1');
    }

    public function committingANestedTransactionReleasesItsSavepoint(): void
    {
        $driver = SQLiteDriver::create(new SQLiteDriverConfig());
        $logger = new SQLiteDriverTestLogger();
        $driver->setLogger($logger);
        $driver->beginTransaction();
        $driver->beginTransaction();

        $driver->commitTransaction();

        Assert::contains($logger->records, ['info', 'RELEASE SAVEPOINT "SVP2"']);
    }

    public function warnsThatIsolationLevelsAreNotSupported(): void
    {
        $driver = SQLiteDriver::create(new SQLiteDriverConfig());
        $logger = new SQLiteDriverTestLogger();
        $driver->setLogger($logger);

        $driver->beginTransaction('SERIALIZABLE');

        Assert::contains(
            $logger->records,
            ['alert', 'Transaction isolation level is not fully supported by SQLite (SERIALIZABLE)'],
        );
    }

    public function ignoresTheIsolationLevelWithoutALogger(): void
    {
        $driver = SQLiteDriver::create(new SQLiteDriverConfig());

        Assert::true($driver->beginTransaction('SERIALIZABLE'));
    }

    public function logsTheQueryAndTheErrorOfAFailedStatement(): void
    {
        $driver = SQLiteDriver::create(new SQLiteDriverConfig());
        $logger = new SQLiteDriverTestLogger();
        $driver->setLogger($logger);

        try {
            $driver->query('SELECT * FROM missing');
        } catch (StatementException) {
        }

        Assert::same($logger->records[0], ['error', 'SELECT * FROM missing']);
        Assert::same($logger->records[1][0], 'alert');
        Assert::string($logger->records[1][1])->contains('no such table: missing');
    }

    public function logsTheElapsedTimeOfAStatement(): void
    {
        $driver = SQLiteDriver::create(new SQLiteDriverConfig());
        $logger = new SQLiteDriverTestLogger();
        $driver->setLogger($logger);

        $driver->query('SELECT 1');

        Assert::float($logger->contexts[0]['elapsed'])->greaterThanOrEqual(0.0)->lessThan(60.0);
    }

    public function keepsTheMicrosecondsOfAMutableDatetime(): void
    {
        $driver = SQLiteDriver::create(new SQLiteDriverConfig(options: ['withDatetimeMicroseconds' => true]));

        $quoted = $driver->quote(new \DateTime('2000-01-23 01:23:45.678901', new \DateTimeZone('UTC')));

        Assert::same($quoted, "'2000-01-23 01:23:45.678901'");
    }

    public function describesItselfForDebugging(): void
    {
        $config = new SQLiteDriverConfig();
        $driver = SQLiteDriver::create($config);

        Assert::same($driver->__debugInfo(), [
            'connection' => $config->connection,
            'source' => ':memory:',
            'connected' => false,
            'options' => $config,
        ]);
    }

    public function answersTheDeprecatedMethods(): void
    {
        $driver = SQLiteDriver::create(new SQLiteDriverConfig());
        $driver->execute('CREATE TABLE t (id INTEGER)');

        Assert::true($driver->isProfiling());
        Assert::null($driver->setProfiling(true));
        Assert::same($driver->tableNames(), ['t']);
        Assert::true($driver->hasTable('t'));
        Assert::same($driver->identifier('t'), '"t"');
        Assert::same($driver->getSchema('t', 'p_')->getFullName(), 'p_t');
        Assert::instanceOf($driver->insertQuery('', 't'), InsertQuery::class);
        Assert::instanceOf($driver->selectQuery('', ['t']), SelectQuery::class);
        Assert::instanceOf($driver->updateQuery('', 't'), UpdateQuery::class);
        Assert::instanceOf($driver->deleteQuery('', 't'), DeleteQuery::class);
    }

    public function erasesTheDataOfATableThroughTheDeprecatedMethod(): void
    {
        $driver = SQLiteDriver::create(new SQLiteDriverConfig());
        $driver->execute('CREATE TABLE t (id INTEGER)');
        $driver->execute('INSERT INTO t VALUES (1)');

        $driver->eraseData('t');

        Assert::same($driver->query('SELECT COUNT(*) FROM t')->fetchColumn(), 0);
    }

    public function rejectsAnUnknownMethod(): never
    {
        Expect::exception(DriverException::class)->withMessage('Undefined driver method `unknown`');

        SQLiteDriver::create(new SQLiteDriverConfig())->unknown();
    }

    public function cursorNeedsATransaction(): never
    {
        $driver = SQLiteDriver::create(new SQLiteDriverConfig());

        Expect::exception(DriverException::class)->withMessage(
            'SQLite cursor requires an active transaction to guarantee snapshot consistency. '
            . 'Wrap the cursor iteration in Database::transaction() or call beginTransaction() before cursor().',
        );

        $driver->cursor('SELECT 1')->current();
    }

    public function iteratingAStatementYieldsAssociativeRows(): void
    {
        $driver = SQLiteDriver::create(new SQLiteDriverConfig());

        $rows = \iterator_to_array($driver->query('SELECT 1 AS a, 2 AS b'));

        Assert::same($rows, [['a' => 1, 'b' => 2]]);
    }
}

/**
 * Records every log call as a [level, message] pair and its context alongside.
 */
final class SQLiteDriverTestLogger extends AbstractLogger
{
    /** @var list<array{string, string}> */
    public array $records = [];

    /** @var list<array> */
    public array $contexts = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->records[] = [(string) $level, (string) $message];
        $this->contexts[] = $context;
    }
}
