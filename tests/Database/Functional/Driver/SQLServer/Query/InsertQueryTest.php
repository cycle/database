<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Query;

// phpcs:ignore
use Cycle\Database\Driver\SQLServer\Query\SQLServerInsertQuery;
use Cycle\Database\Driver\SQLServer\Schema\SQLServerColumn;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Tests\Functional\Driver\Common\Query\InsertQueryTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
final class InsertQueryTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    #[\Testo\Test]
    public function testQueryInstance(): void
    {
        parent::testQueryInstance();
        \Testo\Assert::instanceOf($this->database->insert(), SQLServerInsertQuery::class);
    }

    #[\Testo\Test]
    public function testReturning(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning('name');

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) OUTPUT INSERTED.{name} VALUES (?,?)',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testMultipleReturning(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning('name', 'created_at');

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) OUTPUT INSERTED.{name}, INSERTED.{created_at} VALUES (?,?)',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testReturningWithFragment(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning(new Fragment('INSERTED.[name] as [full_name]'));

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) OUTPUT INSERTED.{name} as {full_name} VALUES (?,?)',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testReturningWithFragmentWithParameter(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning(new Fragment('INSERTED.[balance] + 100 as [modified_balance]'));

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) OUTPUT INSERTED.{balance} + 100 as {modified_balance} VALUES (?,?)',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testMultipleReturningWithFragment(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning('name', new Fragment('INSERTED.[created_at] as [date]'));

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) OUTPUT INSERTED.{name}, INSERTED.{created_at} as {date} VALUES (?,?)',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testReturningWithDefaultValues(): void
    {
        $insert = $this->database->insert()->into('table')->values([])->returning('created_at');

        $this->assertSameQuery(
            'INSERT INTO {table} OUTPUT INSERTED.{created_at} DEFAULT VALUES',
            $insert,
        );
    }

    #[\Testo\Test]
    public function testReturningValuesFromDatabase(): void
    {
        $schema = $this->schema('returning_values');
        $schema->primary('id');
        $schema->string('name');
        $schema->datetime('created_at', defaultValue: SQLServerColumn::DATETIME_NOW);
        $schema->save();

        $returning = $this->database
            ->insert('returning_values')
            ->values(['name' => 'foo'])
            ->returning('id', 'created_at')
            ->run();

        \Testo\Assert::same((int) $returning['id'], 1);
        \Testo\Assert::string($returning['created_at']);
        \Testo\Assert::notSame(\strtotime($returning['created_at']), false);

        $returning = $this->database
            ->insert('returning_values')
            ->values(['name' => 'foo'])
            ->returning('id', new Fragment('INSERTED.[created_at] as [datetime]'))
            ->run();

        \Testo\Assert::same((int) $returning['id'], 2);
        \Testo\Assert::string($returning['datetime']);
        \Testo\Assert::notSame(\strtotime($returning['datetime']), false);
    }

    #[\Testo\Test]
    public function testReturningSingleValueFromDatabase(): void
    {
        $schema = $this->schema('returning_value');
        $schema->primary('id');
        $schema->string('name');
        $schema->datetime('created_at', defaultValue: SQLServerColumn::DATETIME_NOW);
        $schema->save();

        $returning = $this->database
            ->insert('returning_value')
            ->values(['name' => 'foo'])
            ->returning(new Fragment('INSERTED.[created_at] as [datetime]'))
            ->run();

        \Testo\Assert::string($returning);
        \Testo\Assert::notSame(\strtotime($returning), false);
    }

    #[\Testo\Test]
    public function testReturningValuesFromDatabaseWithDefaultValuesInsert(): void
    {
        $schema = $this->schema('returning_value');
        $schema->primary('id');
        $schema->datetime('created_at', defaultValue: SQLServerColumn::DATETIME_NOW);
        $schema->datetime('updated_at', defaultValue: SQLServerColumn::DATETIME_NOW);
        $schema->save();

        $returning = $this->database
            ->insert('returning_value')
            ->values([])
            ->returning('updated_at', new Fragment('INSERTED.[created_at] as [created]'))
            ->run();

        \Testo\Assert::string($returning['created']);
        \Testo\Assert::notSame(\strtotime($returning['created']), false);

        \Testo\Assert::string($returning['updated_at']);
        \Testo\Assert::notSame(\strtotime($returning['updated_at']), false);
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
            ->returning('some_int', new Fragment('INSERTED.[some_int] + ? as [cnt]', 5))
            ->run();

        \Testo\Assert::same((int) $returning['cnt'], 9);
    }

    #[\Testo\Test]
    public function testCustomReturningShouldContainColumns(): void
    {
        \Testo\Expect::exception(BuilderException::class)->withMessageContaining('RETURNING clause should contain at least 1 column.');

        $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning();
    }
}
