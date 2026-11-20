<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

// phpcs:ignore
use Cycle\Database\Driver\Postgres\Query\PostgresInsertQuery;
use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Tests\Functional\Driver\Common\Query\InsertQueryTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class InsertQueryTest extends CommonClass
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testQueryInstance(): void
    {
        parent::testQueryInstance();
        \Testo\Assert::instanceOf($this->database->insert(), PostgresInsertQuery::class);
    }

    #[\Testo\Test]
    public function testSimpleInsert(): void
    {
        $insert = $this->database->insert()->into('target_table')->values(
            [
                'name' => 'Anton',
            ],
        );

        $this->assertSameQuery(
            'INSERT INTO {target_table} ({name}) VALUES (?) RETURNING {target_id}',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testSimpleInsertWithStatesValues(): void
    {
        $insert = $this->database->insert()->into('target_table')
            ->columns('name', 'balance')
            ->values('Anton', 100);

        $this->assertSameQuery(
            'INSERT INTO {target_table} ({name}, {balance}) VALUES (?, ?) RETURNING {target_id}',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testSimpleInsertMultipleRows(): void
    {
        $insert = $this->database->insert()->into('target_table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->values('John', 200);

        $this->assertSameQuery(
            'INSERT INTO {target_table} ({name}, {balance}) VALUES (?, ?), (?, ?) RETURNING {target_id}',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testCustomReturning(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->returning('name');

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?) RETURNING {name}',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testCustomMultipleReturning(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->returning('name', 'created_at');

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?) RETURNING {name}, {created_at}',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testCustomReturningWithFragment(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->returning(new Fragment('"name" as "full_name"'));

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?) RETURNING {name} as {full_name}',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testCustomReturningWithFragmentWithParameter(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning(new Fragment('"balance" + 100 as "modified_balance"'));

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) VALUES (?,?) RETURNING {balance} + 100 as {modified_balance}',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testCustomMultipleReturningWithFragment(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->returning('name', new Fragment('"created_at" as "date"'));

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?) RETURNING {name}, {created_at} as {date}',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testReturningValuesFromDatabase(): void
    {
        $schema = $this->schema('returning_values');
        $schema->primary('id');
        $schema->string('name');
        $schema->serial('sort');
        $schema->datetime('datetime', defaultValue: PostgresColumn::DATETIME_NOW);
        $schema->save();

        $returning = $this->database
            ->insert('returning_values')
            ->values(['name' => 'foo'])
            ->returning('sort', 'datetime')
            ->run();

        \Testo\Assert::same($returning['sort'], 1);
        \Testo\Assert::string($returning['datetime']);
        \Testo\Assert::notSame(\strtotime($returning['datetime']), false);

        $returning = $this->database
            ->insert('returning_values')
            ->values(['name' => 'foo'])
            ->returning('sort', new Fragment('"datetime" as "created_at"'))
            ->run();

        \Testo\Assert::same($returning['sort'], 2);
        \Testo\Assert::string($returning['created_at']);
        \Testo\Assert::notSame(\strtotime($returning['created_at']), false);
    }

    #[\Testo\Test]
    public function testReturningSingleValueFromDatabase(): void
    {
        $schema = $this->schema('returning_value');
        $schema->primary('id');
        $schema->string('name');
        $schema->serial('sort');
        $schema->save();

        $returning = $this->database
            ->insert('returning_value')
            ->values(['name' => 'foo'])
            ->returning('sort')
            ->run();

        \Testo\Assert::same($returning, 1);

        $returning = $this->database
            ->insert('returning_value')
            ->values(['name' => 'foo'])
            ->returning(new Fragment('"sort" as "number"'))
            ->run();

        \Testo\Assert::same($returning, 2);
    }

    #[\Testo\Test]
    public function testReturningFromDatabaseWithFragmentWithParameter(): void
    {
        $schema = $this->schema('returning_value');
        $schema->primary('id');
        $schema->integer('some_int');
        $schema->save();

        $returning = $this->database
            ->insert('returning_value')
            ->values(['some_int' => 4])
            ->returning('some_int', new Fragment('"some_int" + ? as "cnt"', 5))
            ->run();

        \Testo\Assert::same((int) $returning['cnt'], 9);
    }

    #[\Testo\Test]
    public function testCustomReturningShouldContainColumns(): void
    {
        \Testo\Expect::exception(BuilderException::class)->withMessageContaining('RETURNING clause should contain at least 1 column.');

        $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->returning();
    }

    #[\Testo\Test]
    public function testInsertMicroseconds(): void
    {
        $schema = $this->schema(
            table: 'with_microseconds',
            driverConfig: ['options' => ['withDatetimeMicroseconds' => true]],
        );
        $schema->primary('id');
        $schema->datetime('datetime', 6);
        $schema->save();

        $expected = new \DateTimeImmutable();

        $id = $this->db(
            driverConfig: ['options' => ['withDatetimeMicroseconds' => true]],
        )->insert('with_microseconds')->values([
            'datetime' => $expected,
        ])->run();

        $result = $this->db(
            driverConfig: ['options' => ['withDatetimeMicroseconds' => true]],
        )->select('datetime')
            ->from('with_microseconds')
            ->where('id', $id)
            ->run()
            ->fetch();

        \Testo\Assert::string($result['datetime'])->contains('.');
    }

    #[\Testo\Test]
    public function testInsertDatetimeWithoutMicroseconds(): void
    {
        $schema = $this->schema('without_microseconds');
        $schema->primary('id');
        $schema->datetime('datetime');
        $schema->save();

        $expected = new \DateTimeImmutable();

        $id = $this->database->insert('without_microseconds')->values([
            'datetime' => $expected,
        ])->run();

        $result = $this->database->select('datetime')
            ->from('without_microseconds')
            ->where('id', $id)
            ->run()
            ->fetch();

        \Testo\Assert::same($result['datetime'], $expected->setTimezone($this->database->getDriver()->getTimezone())->format('Y-m-d H:i:s'));
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        //To test PG insert behaviour rendering
        $schema = $this->database->table('target_table')->getSchema();
        $schema->primary('target_id');
        $schema->save();
    }

    #[\Testo\Lifecycle\AfterTest]
    public function tearDown(): void
    {
        $this->dropDatabase($this->database);
    }
}
