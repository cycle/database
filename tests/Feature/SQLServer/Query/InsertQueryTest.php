<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLServer\Query;

use Cycle\Database\Driver\SQLServer\Query\SQLServerInsertQuery;
use Cycle\Database\Driver\SQLServer\Schema\SQLServerColumn;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Tests\Feature\Common\Query\InsertQueryTest as CommonClass;
use Cycle\Testing\Database\Assert\SqlAssert;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class InsertQueryTest extends CommonClass
{
    #[Test]
    public function testQueryInstance(): void
    {
        parent::testQueryInstance();
        Assert::instanceOf($this->database->insert(), SQLServerInsertQuery::class);
    }

    #[Test]
    public function testReturning(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning('name');

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {balance}) OUTPUT INSERTED.{name} VALUES (?,?)');
    }

    #[Test]
    public function testMultipleReturning(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning('name', 'created_at');

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {balance}) OUTPUT INSERTED.{name}, INSERTED.{created_at} VALUES (?,?)');
    }

    #[Test]
    public function testReturningWithFragment(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning(new Fragment('INSERTED.[name] as [full_name]'));

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {balance}) OUTPUT INSERTED.{name} as {full_name} VALUES (?,?)');
    }

    #[Test]
    public function testReturningWithFragmentWithParameter(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning(new Fragment('INSERTED.[balance] + 100 as [modified_balance]'));

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {balance}) OUTPUT INSERTED.{balance} + 100 as {modified_balance} VALUES (?,?)');
    }

    #[Test]
    public function testMultipleReturningWithFragment(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning('name', new Fragment('INSERTED.[created_at] as [date]'));

        SqlAssert::same($insert, 'INSERT INTO {table} ({name}, {balance}) OUTPUT INSERTED.{name}, INSERTED.{created_at} as {date} VALUES (?,?)');
    }

    #[Test]
    public function testReturningWithDefaultValues(): void
    {
        $insert = $this->database->insert()->into('table')->values([])->returning('created_at');

        SqlAssert::same($insert, 'INSERT INTO {table} OUTPUT INSERTED.{created_at} DEFAULT VALUES');
    }

    #[Test]
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

        Assert::same((int) $returning['id'], 1);
        Assert::string($returning['created_at']);
        Assert::notSame(\strtotime($returning['created_at']), false);

        $returning = $this->database
            ->insert('returning_values')
            ->values(['name' => 'foo'])
            ->returning('id', new Fragment('INSERTED.[created_at] as [datetime]'))
            ->run();

        Assert::same((int) $returning['id'], 2);
        Assert::string($returning['datetime']);
        Assert::notSame(\strtotime($returning['datetime']), false);
    }

    #[Test]
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

        Assert::string($returning);
        Assert::notSame(\strtotime($returning), false);
    }

    #[Test]
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

        Assert::string($returning['created']);
        Assert::notSame(\strtotime($returning['created']), false);

        Assert::string($returning['updated_at']);
        Assert::notSame(\strtotime($returning['updated_at']), false);
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
            ->returning('some_int', new Fragment('INSERTED.[some_int] + ? as [cnt]', 5))
            ->run();

        Assert::same((int) $returning['cnt'], 9);
    }

    #[Test]
    public function testCustomReturningShouldContainColumns(): void
    {
        Expect::exception(BuilderException::class)->withMessageContaining('RETURNING clause should contain at least 1 column.');

        $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning();
    }
}
