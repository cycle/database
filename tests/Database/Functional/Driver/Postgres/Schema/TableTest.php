<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\TableTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class TableTest extends CommonClass
{
    public const DRIVER = 'postgres';

    //Applause, PG
    public function testGetColumns(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $expected = [
            'id' => 'primary',
            'name' => 'text',
            'value' => 'integer',
        ];
        \arsort($expected);

        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[$column->getName()] = $column->getAbstractType();
        }

        \arsort($columns);

        $this->assertSame($expected, $columns);
    }

    public function testSelectDistinct(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['Anton', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        $data = $table->select('name', 'value')->distinct('name')->fetchAll();
        $this->assertCount(4, $data);
    }

    public function testSelectDistinctOn(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['Anton', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        $data = $table->select('name', 'value')->distinctOn('name')->fetchAll();
        $this->assertCount(3, $data);
    }

    public function testDependencies(): void
    {
        $schema = $this->database->table('table2')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();

        $table = $this->database->table('table');

        $this->assertCount(0, $table->getDependencies());

        $schema = $table->getSchema();
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('table2', ['id']);
        $schema->save();

        $this->assertSame(['public.table2'], $table->getDependencies());
    }

    public function testUpsertMultipleRows(): void
    {
        $schema = $this->schema('foo');
        $schema->primary('id');
        $schema->string('name')->nullable(false);
        $schema->string('email', 64)->nullable(false);
        $schema->integer('balance')->defaultValue(0);
        $schema->index(['email'])->unique(true);
        $schema->save();

        $table = $this->database->table('foo');

        $this->assertTrue($table->exists());
        $this->assertSame(0, $table->count());

        $insertId = $table->insertOne(
            ['name' => 'Anton', 'email' => 'anton@email.com', 'balance' => 10],
        );

        $this->assertNotNull($insertId);
        $this->assertSame(1, $insertId);
        $this->assertSame(1, $table->count());
        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'Anton', 'email' => 'anton@email.com', 'balance' => 10],
            ],
            $table->fetchAll(),
        );

        $table->upsertMultiple(
            ['name', 'email', 'balance'],
            [
                ['Anton', 'anton@email.com', 50],
                ['Adam', 'adam@email.com', 100],
                ['John', 'john@email.com', 400],
                ['Mark', 'mark@email.com', 800],
            ],
            'email'
        );

        $this->assertSame(4, $table->count());
        // Postgres sequences ends up N+1 when upserting
        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'Anton', 'email' => 'anton@email.com', 'balance' => 50],
                ['id' => 3, 'name' => 'Adam', 'email' => 'adam@email.com', 'balance' => 100],
                ['id' => 4, 'name' => 'John', 'email' => 'john@email.com', 'balance' => 400],
                ['id' => 5, 'name' => 'Mark', 'email' => 'mark@email.com', 'balance' => 800],
            ],
            $table->fetchAll(),
        );
    }
}
