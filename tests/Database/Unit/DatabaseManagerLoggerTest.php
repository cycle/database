<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLite\MemoryConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Database;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\LoggerFactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class DatabaseManagerLoggerTest extends TestCase
{
    public function testSetLoggerBeforeDriversAreCreated(): void
    {
        $dbal = $this->createManager();
        $dbal->setLogger($logger = new RecordingLogger());

        $dbal->database('default')->query('SELECT 1')->fetchAll();

        $this->assertLogged($logger, 'SELECT 1');
    }

    public function testSetLoggerAfterDatabaseIsCreated(): void
    {
        $dbal = $this->createManager();
        $db = $dbal->database('default');
        $dbal->setLogger($logger = new RecordingLogger());

        $db->query('SELECT 1')->fetchAll();

        $this->assertLogged($logger, 'SELECT 1');
    }

    public function testSetLoggerAfterDriverIsCreated(): void
    {
        $dbal = $this->createManager();
        $driver = $dbal->driver('write');
        $dbal->setLogger($logger = new RecordingLogger());

        $driver->query('SELECT 1')->fetchAll();

        $this->assertLogged($logger, 'SELECT 1');
    }

    public function testSetLoggerAfterGetDrivers(): void
    {
        $dbal = $this->createManager();
        $drivers = $dbal->getDrivers();
        $dbal->setLogger($logger = new RecordingLogger());

        foreach ($drivers as $driver) {
            $driver->query('SELECT 1')->fetchAll();
        }

        $this->assertSame(\count($drivers), $logger->count('SELECT 1'));
    }

    public function testSetLoggerReachesReadDriver(): void
    {
        $dbal = $this->createManager();
        $db = $dbal->database('default');
        $dbal->setLogger($logger = new RecordingLogger());

        $db->getDriver(DatabaseInterface::READ)->query('SELECT 1')->fetchAll();

        $this->assertNotSame($db->getDriver(DatabaseInterface::READ), $db->getDriver(DatabaseInterface::WRITE));
        $this->assertLogged($logger, 'SELECT 1');
    }

    public function testRepeatedSetLoggerReplacesLoggerEverywhere(): void
    {
        $dbal = $this->createManager();
        $db = $dbal->database('default');
        $dbal->setLogger($first = new RecordingLogger());
        $dbal->setLogger($second = new RecordingLogger());

        $db->query('SELECT 1')->fetchAll();
        $dbal->database('other')->query('SELECT 2')->fetchAll();

        $this->assertSame([], $first->messages);
        $this->assertLogged($second, 'SELECT 1');
        $this->assertLogged($second, 'SELECT 2');
    }

    public function testSetLoggerOverridesLoggerSetOnDriver(): void
    {
        $dbal = $this->createManager();
        $driver = $dbal->driver('write');
        $driver->setLogger($own = new RecordingLogger());
        $dbal->setLogger($global = new RecordingLogger());

        $driver->query('SELECT 1')->fetchAll();

        $this->assertSame([], $own->messages);
        $this->assertLogged($global, 'SELECT 1');
    }

    public function testLoggerFactoryIsUsedForNewDrivers(): void
    {
        $dbal = $this->createManager($factory = new RecordingLoggerFactory());

        $dbal->database('default')->query('SELECT 1')->fetchAll();

        $this->assertLogged($factory->loggers['read'], 'SELECT 1');
    }

    public function testSetLoggerWithLoggerFactoryBeforeDriversAreCreated(): void
    {
        $dbal = $this->createManager(new RecordingLoggerFactory());
        $dbal->setLogger($logger = new RecordingLogger());

        $dbal->database('default')->query('SELECT 1')->fetchAll();

        $this->assertLogged($logger, 'SELECT 1');
    }

    public function testSetLoggerWithLoggerFactoryAfterDriversAreCreated(): void
    {
        $dbal = $this->createManager(new RecordingLoggerFactory());
        $db = $dbal->database('default');
        $dbal->setLogger($logger = new RecordingLogger());

        $db->query('SELECT 1')->fetchAll();
        $dbal->database('other')->query('SELECT 2')->fetchAll();

        $this->assertLogged($logger, 'SELECT 1');
        $this->assertLogged($logger, 'SELECT 2');
    }

    public function testDriverAddedAfterSetLogger(): void
    {
        $dbal = $this->createManager();
        $dbal->setLogger($logger = new RecordingLogger());
        $dbal->addDriver('manual', $driver = $this->createDriver());

        $driver->query('SELECT 1')->fetchAll();

        $this->assertLogged($logger, 'SELECT 1');
    }

    public function testDriverAddedBeforeSetLogger(): void
    {
        $dbal = $this->createManager();
        $dbal->addDriver('manual', $driver = $this->createDriver());
        $dbal->setLogger($logger = new RecordingLogger());

        $driver->query('SELECT 1')->fetchAll();

        $this->assertLogged($logger, 'SELECT 1');
    }

    public function testDatabaseAddedBeforeSetLogger(): void
    {
        $dbal = $this->createManager();
        $dbal->addDatabase(new Database('manual', '', $this->createDriver()));
        $dbal->setLogger($logger = new RecordingLogger());

        $dbal->database('manual')->query('SELECT 1')->fetchAll();

        $this->assertLogged($logger, 'SELECT 1');
    }

    public function testDatabaseAddedAfterSetLogger(): void
    {
        $dbal = $this->createManager();
        $dbal->setLogger($logger = new RecordingLogger());
        $dbal->addDatabase(new Database('manual', '', $this->createDriver()));

        $dbal->database('manual')->query('SELECT 1')->fetchAll();

        $this->assertLogged($logger, 'SELECT 1');
    }

    public function testDatabaseWithoutCacheCreatedBeforeSetLoggerKeepsPreviousLogger(): void
    {
        $dbal = $this->createManager();
        $dbal->setLogger($previous = new RecordingLogger());
        $db = $dbal->database('default')->withoutCache();
        $dbal->setLogger($logger = new RecordingLogger());

        $db->query('SELECT 1')->fetchAll();

        $this->assertSame([], $logger->messages);
        $this->assertLogged($previous, 'SELECT 1');
    }

    public function testDatabaseWithoutCacheCreatedAfterSetLogger(): void
    {
        $dbal = $this->createManager();
        $dbal->setLogger($logger = new RecordingLogger());
        $db = $dbal->database('default')->withoutCache();

        $db->query('SELECT 1')->fetchAll();

        $this->assertLogged($logger, 'SELECT 1');
    }

    public function testDatabaseWithPrefixCreatedBeforeSetLogger(): void
    {
        $dbal = $this->createManager();
        $db = $dbal->database('default')->withPrefix('p_');
        $dbal->setLogger($logger = new RecordingLogger());

        $db->query('SELECT 1')->fetchAll();

        $this->assertLogged($logger, 'SELECT 1');
    }

    public function testSetLoggerSurvivesReconnect(): void
    {
        $dbal = $this->createManager();
        $driver = $dbal->driver('write');
        $driver->query('SELECT 0')->fetchAll();
        $dbal->setLogger($logger = new RecordingLogger());

        $driver->disconnect();
        $driver->query('SELECT 1')->fetchAll();

        $this->assertLogged($logger, 'SELECT 1');
    }

    public function testSetLoggerLogsTransactions(): void
    {
        $dbal = $this->createManager();
        $db = $dbal->database('default');
        $dbal->setLogger($logger = new RecordingLogger());

        $db->transaction(static function (Database $db): void {
            $db->transaction(static fn(Database $db) => $db->query('SELECT 1')->fetchAll());
        });
        $db->begin();
        $db->rollback();

        $this->assertLogged($logger, 'Begin transaction');
        $this->assertLogged($logger, "Transaction: new savepoint 'SVP2'");
        $this->assertLogged($logger, "Transaction: release savepoint 'SVP2'");
        $this->assertLogged($logger, 'Commit transaction');
        $this->assertLogged($logger, 'Rollback transaction');
        $this->assertLogged($logger, 'SELECT 1');
    }

    public function testNullLoggerSilencesDrivers(): void
    {
        $dbal = $this->createManager();
        $db = $dbal->database('default');
        $dbal->setLogger($logger = new RecordingLogger());
        $dbal->setLogger(new NullLogger());

        $db->query('SELECT 1')->fetchAll();
        $dbal->database('other')->query('SELECT 2')->fetchAll();

        $this->assertSame([], $logger->messages);
    }

    public function testNullLoggerIsPassedToNewDrivers(): void
    {
        $dbal = $this->createManager();
        $dbal->setLogger($logger = new NullLogger());

        $driver = $dbal->driver('write');

        $this->assertSame($logger, (fn() => $this->logger)->call($driver));
    }

    private function createManager(?LoggerFactoryInterface $factory = null): DatabaseManager
    {
        return new DatabaseManager(
            new DatabaseConfig([
                'default' => 'default',
                'databases' => [
                    'default' => ['write' => 'write', 'read' => 'read'],
                    'other' => ['driver' => 'other'],
                ],
                'connections' => [
                    'write' => new SQLiteDriverConfig(connection: new MemoryConnectionConfig()),
                    'read' => new SQLiteDriverConfig(connection: new MemoryConnectionConfig()),
                    'other' => new SQLiteDriverConfig(connection: new MemoryConnectionConfig()),
                ],
            ]),
            $factory,
        );
    }

    private function createDriver(): DriverInterface
    {
        return SQLiteDriver::create(new SQLiteDriverConfig(connection: new MemoryConnectionConfig()));
    }

    private function assertLogged(RecordingLogger $logger, string $message): void
    {
        $this->assertGreaterThan(
            0,
            $logger->count($message),
            \sprintf("Message '%s' is not logged. Logged: %s", $message, \json_encode($logger->messages)),
        );
    }
}

final class RecordingLogger extends AbstractLogger
{
    /** @var list<string> */
    public array $messages = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->messages[] = (string) $message;
    }

    public function count(string $message): int
    {
        return \count(\array_filter($this->messages, static fn(string $m) => \trim($m) === $message));
    }
}

final class RecordingLoggerFactory implements LoggerFactoryInterface
{
    /** @var array<string, RecordingLogger> */
    public array $loggers = [];

    public function getLogger(?DriverInterface $driver = null): LoggerInterface
    {
        return $this->loggers[$driver?->getName() ?? ''] ??= new RecordingLogger();
    }
}
