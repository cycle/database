<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Query;

// phpcs:ignore
use Cycle\Database\Driver\SQLServer\Query\SQLServerInsertQuery;
use Cycle\Database\Driver\SQLServer\Schema\SQLServerColumn;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Tests\Functional\Driver\Common\Query\InsertQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
final class InsertQueryTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    public function testQueryInstance(): void
    {
        parent::testQueryInstance();
        $this->assertInstanceOf(SQLServerInsertQuery::class, $this->database->insert());
    }

    public function testReturning(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning('name');

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) OUTPUT INSERTED.{name} VALUES (?,?)',
            $insert
        );
    }

    public function testMultipleReturning(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning('name', 'created_at');

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) OUTPUT INSERTED.{name}, INSERTED.{created_at} VALUES (?,?)',
            $insert
        );
    }

    public function testReturningWithFragment(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning(new Fragment('INSERTED.[name] as [full_name]'));

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) OUTPUT INSERTED.{name} as {full_name} VALUES (?,?)',
            $insert
        );
    }

    public function testMultipleReturningWithFragment(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning('name', new Fragment('INSERTED.[created_at] as [date]'));

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) OUTPUT INSERTED.{name}, INSERTED.{created_at} as {date} VALUES (?,?)',
            $insert
        );
    }

    public function testReturningWithDefaultValues(): void
    {
        $insert = $this->database->insert()->into('table')->values([])->returning('created_at');

        $this->assertSameQuery(
            'INSERT INTO {table} OUTPUT INSERTED.[created_at] DEFAULT VALUES',
            $insert
        );
    }

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

        $this->assertSame(1, (int) $returning['id']);
        $this->assertIsString($returning['created_at']);
        $this->assertNotFalse(\strtotime($returning['created_at']));

        $returning = $this->database
            ->insert('returning_values')
            ->values(['name' => 'foo'])
            ->returning('id', new Fragment('INSERTED.[created_at] as [datetime]'))
            ->run();

        $this->assertSame(2, (int) $returning['id']);
        $this->assertIsString($returning['datetime']);
        $this->assertNotFalse(\strtotime($returning['datetime']));
    }

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

        $this->assertIsString($returning);
        $this->assertNotFalse(\strtotime($returning));
    }

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

        $this->assertIsString($returning['created']);
        $this->assertNotFalse(\strtotime($returning['created']));

        $this->assertIsString($returning['updated_at']);
        $this->assertNotFalse(\strtotime($returning['updated_at']));
    }

    public function testCustomReturningShouldContainColumns(): void
    {
        $this->expectException(BuilderException::class);
        $this->expectExceptionMessage('RETURNING clause should contain at least 1 column.');

        $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('John Doe', 100)
            ->returning();
    }
}
