<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Query;

use Cycle\Database\Driver\Postgres\Query\PostgresInsertQuery;
use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Tests\Feature\Common\Query\InsertQueryTest as CommonClass;
use Cycle\Testing\Database\Assert\SqlAssert;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class InsertQueryTest extends CommonClass
{
    #[Test]
    public function testQueryInstance(): void
    {
        parent::testQueryInstance();
        Assert::instanceOf($this->database->insert(), PostgresInsertQuery::class);
    }

    #[Test]
    public function testSimpleInsert(): void
    {
        $insert = $this->database->insert()->into('target_table')->values(
            [
                'name' => 'Anton',
            ],
        );

        SqlAssert::same($insert, 'INSERT INTO {target_table} ({name}) VALUES (?) RETURNING {target_id}');
    }

    #[Test]
    public function testSimpleInsertWithStatesValues(): void
    {
        $insert = $this->database->insert()->into('target_table')
            ->columns('name', 'balance')
            ->values('Anton', 100);

        SqlAssert::same($insert, 'INSERT INTO {target_table} ({name}, {balance}) VALUES (?, ?) RETURNING {target_id}');
    }

    #[Test]
    public function testSimpleInsertMultipleRows(): void
    {
        $insert = $this->database->insert()->into('target_table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->values('John', 200);

        SqlAssert::same($insert, 'INSERT INTO {target_table} ({name}, {balance}) VALUES (?, ?), (?, ?) RETURNING {target_id}');
    }

    #[Test]
    public function testCustomReturning(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->returning('name');

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?) RETURNING {name}');
    }

    #[Test]
    public function testCustomMultipleReturning(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->returning('name', 'created_at');

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?) RETURNING {name}, {created_at}');
    }

    #[Test]
    public function testCustomReturningWithFragment(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->returning(new Fragment('"name" as "full_name"'));

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?) RETURNING {name} as {full_name}');
    }

    #[Test]
    public function testCustomReturningWithFragmentWithParameter(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning(new Fragment('"balance" + 100 as "modified_balance"'));

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {balance}) VALUES (?,?) RETURNING {balance} + 100 as {modified_balance}');
    }

    #[Test]
    public function testCustomMultipleReturningWithFragment(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->returning('name', new Fragment('"created_at" as "date"'));

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {balance}) VALUES (?, ?) RETURNING {name}, {created_at} as {date}');
    }

    #[Test]
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

        Assert::same($returning['sort'], 1);
        Assert::string($returning['datetime']);
        Assert::notSame(\strtotime($returning['datetime']), false);

        $returning = $this->database
            ->insert('returning_values')
            ->values(['name' => 'foo'])
            ->returning('sort', new Fragment('"datetime" as "created_at"'))
            ->run();

        Assert::same($returning['sort'], 2);
        Assert::string($returning['created_at']);
        Assert::notSame(\strtotime($returning['created_at']), false);
    }

    #[Test]
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

        Assert::same($returning, 1);

        $returning = $this->database
            ->insert('returning_value')
            ->values(['name' => 'foo'])
            ->returning(new Fragment('"sort" as "number"'))
            ->run();

        Assert::same($returning, 2);
    }

    #[Test]
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

        Assert::same((int) $returning['cnt'], 9);
    }

    #[Test]
    public function testCustomReturningShouldContainColumns(): void
    {
        Expect::exception(BuilderException::class)->withMessageContaining('RETURNING clause should contain at least 1 column.');

        $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->returning();
    }

    #[Test]
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

        Assert::string($result['datetime'])->contains('.');
    }

    #[Test]
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

        Assert::same($result['datetime'], $expected->setTimezone($this->database->getDriver()->getTimezone())->format('Y-m-d H:i:s'));
    }

    #[BeforeTest]
    public function setUp(): void
    {
        //To test PG insert behaviour rendering
        $schema = $this->database->table('target_table')->getSchema();
        $schema->primary('target_id');
        $schema->save();
    }
}
