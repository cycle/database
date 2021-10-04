<?php

declare(strict_types=1);

namespace Cycle\Database\Tests;

use Cycle\Database\Database;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Exception\ReadonlyConnectionException;
use Cycle\Database\Table;

abstract class ReadonlyTest extends BaseTest
{
    /**
     * @var string
     */
    protected $table = 'readonly_tests';

    public function setUp(): void
    {
        $this->database = new Database('default', '', $this->getDriver(['readonly' => true]));

        $this->allowWrite(function () {
            $table = $this->database->table($this->table);
            $schema = $table->getSchema();
            $schema->primary('id');
            $schema->string('value')->nullable();
            $schema->save();
        });
    }

    private function allowWrite(\Closure $then): void
    {
        /** @var Driver $driver */
        $driver = $this->database->getDriver();

        (function (\Closure $then): void {
            $this->options['readonly'] = false;
            try {
                $then();
            } finally {
                $this->options['readonly'] = true;
            }
        })->call($driver, $then);
    }

    public function tearDown(): void
    {
        $this->allowWrite(function () {
            $schema = $this->database->table($this->table)
                ->getSchema();

            $schema->declareDropped();
            $schema->save();
        });
    }

    protected function table(): Table
    {
        return $this->database->table($this->table);
    }

    public function testTableAllowSelection(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->select()
            ->run()
        ;
    }

    public function testTableAllowCount(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->count()
        ;
    }

    public function testTableAllowExists(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->exists()
        ;
    }

    public function testTableAllowGetPrimaryKeys(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->getPrimaryKeys()
        ;
    }

    public function testTableAllowHasColumn(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->hasColumn('column')
        ;
    }

    public function testTableAllowGetColumns(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->getColumns()
        ;
    }

    public function testTableAllowHasIndex(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->hasIndex(['column'])
        ;
    }

    public function testTableAllowGetIndexes(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->getIndexes()
        ;
    }

    public function testTableAllowHasForeignKey(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->hasForeignKey(['column'])
        ;
    }

    public function testTableAllowGetForeignKeys(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->getForeignKeys()
        ;
    }

    public function testTableAllowGetDependencies(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->getDependencies()
        ;
    }

    public function testTableRejectInsertOne(): void
    {
        $this->expectException(ReadonlyConnectionException::class);

        $this->table()
            ->insertOne(['value' => 'example'])
        ;
    }

    public function testTableRejectInsertMultiple(): void
    {
        $this->expectException(ReadonlyConnectionException::class);

        $this->table()
            ->insertMultiple(['value'], ['example'])
        ;
    }

    public function testTableRejectInsert(): void
    {
        $this->expectException(ReadonlyConnectionException::class);

        $this->table()
            ->insert()
                ->columns('value')
                ->values('example')
            ->run();
    }

    public function testTableRejectUpdate(): void
    {
        $this->expectException(ReadonlyConnectionException::class);

        $this->table()
            ->update(['value' => 'updated'])
            ->run()
        ;
    }

    public function testTableRejectDelete(): void
    {
        $this->expectException(ReadonlyConnectionException::class);

        $this->table()
            ->delete()
            ->run()
        ;
    }

    public function testTableRejectEraseData(): void
    {
        $this->expectException(ReadonlyConnectionException::class);

        $this->table()
            ->eraseData()
        ;
    }

    public function testSchemaRejectSaving(): void
    {
        $this->expectException(ReadonlyConnectionException::class);

        $table = $this->database
            ->table('not_allowed_to_creation');

        $schema = $table->getSchema();
        $schema->primary('id');
        $schema->string('value')->nullable();
        $schema->save();
    }

    public function testDatabaseAllowSelection(): void
    {
        $this->expectNotToPerformAssertions();

        $this->database->select()
            ->from($this->table)
            ->run()
        ;
    }

    public function testDatabaseRejectUpdate(): void
    {
        $this->expectException(ReadonlyConnectionException::class);

        $this->database->update($this->table, ['value' => 'example'])
            ->run()
        ;
    }

    public function testDatabaseRejectInsert(): void
    {
        $this->expectException(ReadonlyConnectionException::class);

        $this->database->insert($this->table)
            ->columns('value')
            ->values('example')
            ->run()
        ;
    }

    public function testDatabaseRejectDelete(): void
    {
        $this->expectException(ReadonlyConnectionException::class);

        $this->database->delete($this->table)
            ->run()
        ;
    }

    public function testDatabaseAllowRawQuery(): void
    {
        $this->expectNotToPerformAssertions();

        $this->database->query('SELECT 1');
    }

    public function testDatabaseRejectRawExecution(): void
    {
        $this->expectException(ReadonlyConnectionException::class);

        $this->database->execute("DROP TABLE {$this->table}");
    }
}
