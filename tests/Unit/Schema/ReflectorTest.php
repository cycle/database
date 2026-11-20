<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Database;
use Cycle\Database\Driver\SQLite\Schema\SQLiteTable;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Schema\Reflector;
use Cycle\Database\Tests\Unit\Stub\DecoratedDriver;
use Psr\Log\AbstractLogger;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class ReflectorTest
{
    private Database $db;
    private SQLiteDriver $driver;
    private ReflectorTestLogger $logger;

    #[BeforeTest]
    public function connect(): void
    {
        $this->driver = SQLiteDriver::create(new SQLiteDriverConfig());
        $this->db = new Database('default', '', $this->driver);
        $this->logger = new ReflectorTestLogger();
    }

    public function sortsTablesAfterTheirDependencies(): void
    {
        $parent = $this->table('p');
        $parent->primary('id');
        $child = $this->table('c');
        $child->primary('id');
        $child->integer('p_id');
        $child->foreignKey(['p_id'])->references('p', ['id']);
        $reflector = new Reflector();
        $reflector->addTable($child);
        $reflector->addTable($parent);

        $names = \array_map(static fn(AbstractTable $table): string => $table->getName(), $reflector->sortedTables());

        Assert::same($names, ['p', 'c']);
    }

    public function runsTheChangesInOneCommittedTransaction(): void
    {
        $first = $this->table('a');
        $first->primary('id');
        $second = $this->table('b');
        $second->primary('id');
        $reflector = new Reflector();
        $reflector->addTable($first);
        $reflector->addTable($second);
        $this->driver->setLogger($this->logger);

        $reflector->run();

        Assert::same(\array_count_values($this->logger->messages)['Begin transaction'] ?? 0, 1);
        Assert::contains($this->logger->messages, 'Commit transaction');
        Assert::same($this->driver->getTransactionLevel(), 0);
        Assert::true($this->db->hasTable('a'));
        Assert::true($this->db->hasTable('b'));
    }

    public function doesNothingWithoutChanges(): void
    {
        $table = $this->table('a');
        $table->primary('id');
        $table->save();
        $reflector = new Reflector();
        $reflector->addTable($this->table('a'));
        $this->driver->setLogger($this->logger);

        $reflector->run();

        Assert::same($this->logger->messages, []);
    }

    public function createsTablesListedAfterADroppedOne(): void
    {
        $dropped = $this->table('a');
        $dropped->primary('id');
        $dropped->save();
        $dropped = $this->table('a');
        $dropped->declareDropped();
        $created = $this->table('b');
        $created->primary('id');
        $reflector = new Reflector();
        $reflector->addTable($dropped);
        $reflector->addTable($created);

        $reflector->run();

        Assert::false($this->db->hasTable('a'));
        Assert::true($this->db->hasTable('b'));
    }

    public function beginsATransactionOnADriverThatOnlyImplementsTheInterface(): void
    {
        $driver = DecoratedDriver::create(new SQLiteDriverConfig());
        $table = new SQLiteTable($driver, 't', '');
        $table->primary('id');
        $reflector = new Reflector();
        $reflector->addTable($table);

        $reflector->run();

        Assert::same($driver->transactionCalls, [['begin', [null]], ['commit', []]]);
        Assert::true($driver->getSchemaHandler()->hasTable('t'));
    }

    public function dropsARemovedForeignKey(): void
    {
        $parent = $this->table('p');
        $parent->primary('id');
        $parent->save();
        $child = $this->table('c');
        $child->primary('id');
        $child->integer('p_id');
        $child->foreignKey(['p_id'])->references('p', ['id']);
        $child->save();
        $child = $this->table('c');
        $child->dropForeignKey(['p_id']);
        $reflector = new Reflector();
        $reflector->addTable($child);

        $reflector->run();

        Assert::false($this->table('c')->hasForeignKey(['p_id']));
    }

    public function dropsARemovedIndex(): void
    {
        $table = $this->table('t');
        $table->primary('id');
        $table->integer('a');
        $table->index(['a']);
        $table->save();
        $table = $this->table('t');
        $table->dropIndex(['a']);
        $reflector = new Reflector();
        $reflector->addTable($table);

        $reflector->run();

        Assert::false($this->table('t')->hasIndex(['a']));
    }

    private function table(string $name): AbstractTable
    {
        return $this->db->table($name)->getSchema();
    }
}

/**
 * Collects the messages of every log call.
 */
final class ReflectorTestLogger extends AbstractLogger
{
    /** @var list<string> */
    public array $messages = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->messages[] = (string) $message;
    }
}
