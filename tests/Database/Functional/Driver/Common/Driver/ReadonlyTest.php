<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Driver;

use Cycle\Database\Driver\Driver;
use Cycle\Database\Exception\ReadonlyConnectionException;
use Cycle\Database\Table;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class ReadonlyTest extends BaseTest
{
    /**
     * @var string
     */
    protected $table = 'readonly_tests';

    #[\Testo\Test]
    public function testTableAllowSelection(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->select()
            ->run()
        ;
    }

    #[\Testo\Test]
    public function testTableAllowCount(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->count()
        ;
    }

    #[\Testo\Test]
    public function testTableAllowExists(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->exists()
        ;
    }

    #[\Testo\Test]
    public function testTableAllowGetPrimaryKeys(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->getPrimaryKeys()
        ;
    }

    #[\Testo\Test]
    public function testTableAllowHasColumn(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->hasColumn('column')
        ;
    }

    #[\Testo\Test]
    public function testTableAllowGetColumns(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->getColumns()
        ;
    }

    #[\Testo\Test]
    public function testTableAllowHasIndex(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->hasIndex(['column'])
        ;
    }

    #[\Testo\Test]
    public function testTableAllowGetIndexes(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->getIndexes()
        ;
    }

    #[\Testo\Test]
    public function testTableAllowHasForeignKey(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->hasForeignKey(['column'])
        ;
    }

    #[\Testo\Test]
    public function testTableAllowGetForeignKeys(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->getForeignKeys()
        ;
    }

    #[\Testo\Test]
    public function testTableAllowGetDependencies(): void
    {
        $this->expectNotToPerformAssertions();

        $this->table()
            ->getDependencies()
        ;
    }

    #[\Testo\Test]
    public function testTableRejectInsertOne(): void
    {
        \Testo\Expect::exception(ReadonlyConnectionException::class);

        $this->table()
            ->insertOne(['value' => 'example'])
        ;
    }

    #[\Testo\Test]
    public function testTableRejectInsertMultiple(): void
    {
        \Testo\Expect::exception(ReadonlyConnectionException::class);

        $this->table()
            ->insertMultiple(['value'], ['example'])
        ;
    }

    #[\Testo\Test]
    public function testTableRejectInsert(): void
    {
        \Testo\Expect::exception(ReadonlyConnectionException::class);

        $this->table()
            ->insert()
            ->columns('value')
            ->values('example')
            ->run();
    }

    #[\Testo\Test]
    public function testTableRejectUpdate(): void
    {
        \Testo\Expect::exception(ReadonlyConnectionException::class);

        $this->table()
            ->update(['value' => 'updated'])
            ->run()
        ;
    }

    #[\Testo\Test]
    public function testTableRejectDelete(): void
    {
        \Testo\Expect::exception(ReadonlyConnectionException::class);

        $this->table()
            ->delete()
            ->run()
        ;
    }

    #[\Testo\Test]
    public function testTableRejectEraseData(): void
    {
        \Testo\Expect::exception(ReadonlyConnectionException::class);

        $this->table()
            ->eraseData()
        ;
    }

    #[\Testo\Test]
    public function testSchemaRejectSaving(): void
    {
        \Testo\Expect::exception(ReadonlyConnectionException::class);

        $table = $this->database
            ->table('not_allowed_to_creation');

        $schema = $table->getSchema();
        $schema->primary('id');
        $schema->string('value')->nullable();
        $schema->save();
    }

    #[\Testo\Test]
    public function testDatabaseAllowSelection(): void
    {
        $this->expectNotToPerformAssertions();

        $this->database->select()
            ->from($this->table)
            ->run()
        ;
    }

    #[\Testo\Test]
    public function testDatabaseRejectUpdate(): void
    {
        \Testo\Expect::exception(ReadonlyConnectionException::class);

        $this->database->update($this->table, ['value' => 'example'])
            ->run()
        ;
    }

    #[\Testo\Test]
    public function testDatabaseRejectInsert(): void
    {
        \Testo\Expect::exception(ReadonlyConnectionException::class);

        $this->database->insert($this->table)
            ->columns('value')
            ->values('example')
            ->run()
        ;
    }

    #[\Testo\Test]
    public function testDatabaseRejectDelete(): void
    {
        \Testo\Expect::exception(ReadonlyConnectionException::class);

        $this->database->delete($this->table)
            ->run()
        ;
    }

    #[\Testo\Test]
    public function testDatabaseAllowRawQuery(): void
    {
        $this->expectNotToPerformAssertions();

        $this->database->query('SELECT 1');
    }

    #[\Testo\Test]
    public function testDatabaseRejectRawExecution(): void
    {
        \Testo\Expect::exception(ReadonlyConnectionException::class);

        $this->database->execute("DROP TABLE {$this->table}");
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        $this->database = $this->db('default', '', ['readonly' => true]);

        $this->allowWrite(function (): void {
            $table = $this->database->table($this->table);
            $schema = $table->getSchema();
            $schema->primary('id');
            $schema->string('value')->nullable();
            $schema->save();
        });
    }

    #[\Testo\Lifecycle\AfterTest]
    public function tearDown(): void
    {
        $this->allowWrite(function (): void {
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

    private function allowWrite(\Closure $then): void
    {
        /** @var Driver $driver */
        $driver = $this->database->getDriver();

        (function (\Closure $then): void {
            $this->config->readonly = false;
            try {
                $then();
            } finally {
                $this->config->readonly = true;
            }
        })->call($driver, $then);
    }
}
