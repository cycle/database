<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Driver;

use Cycle\Database\Driver\Driver as DatabaseDriver;
use Cycle\Database\Exception\ReadonlyConnectionException;
use Cycle\Database\Table;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert\ExpectException;
use Testo\Assert\ExpectNoAssertions;
use Testo\Expect;
use Testo\Lifecycle\AfterTest;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class ReadonlyTest extends DatabaseTestCase
{
    /**
     * @var string
     */
    protected $table = 'readonly_tests';

    #[Test]
    #[ExpectNoAssertions]
    public function testTableAllowSelection(): void
    {
        $this->table()
            ->select()
            ->run()
        ;
    }

    #[Test]
    #[ExpectNoAssertions]
    public function testTableAllowCount(): void
    {
        $this->table()
            ->count()
        ;
    }

    #[Test]
    #[ExpectNoAssertions]
    public function testTableAllowExists(): void
    {
        $this->table()
            ->exists()
        ;
    }

    #[Test]
    #[ExpectNoAssertions]
    public function testTableAllowGetPrimaryKeys(): void
    {
        $this->table()
            ->getPrimaryKeys()
        ;
    }

    #[Test]
    #[ExpectNoAssertions]
    public function testTableAllowHasColumn(): void
    {
        $this->table()
            ->hasColumn('column')
        ;
    }

    #[Test]
    #[ExpectNoAssertions]
    public function testTableAllowGetColumns(): void
    {
        $this->table()
            ->getColumns()
        ;
    }

    #[Test]
    #[ExpectNoAssertions]
    public function testTableAllowHasIndex(): void
    {
        $this->table()
            ->hasIndex(['column'])
        ;
    }

    #[Test]
    #[ExpectNoAssertions]
    public function testTableAllowGetIndexes(): void
    {
        $this->table()
            ->getIndexes()
        ;
    }

    #[Test]
    #[ExpectNoAssertions]
    public function testTableAllowHasForeignKey(): void
    {
        $this->table()
            ->hasForeignKey(['column'])
        ;
    }

    #[Test]
    #[ExpectNoAssertions]
    public function testTableAllowGetForeignKeys(): void
    {
        $this->table()
            ->getForeignKeys()
        ;
    }

    #[Test]
    #[ExpectNoAssertions]
    public function testTableAllowGetDependencies(): void
    {
        $this->table()
            ->getDependencies()
        ;
    }

    #[Test]
    #[ExpectException(ReadonlyConnectionException::class)]
    public function testTableRejectInsertOne(): void
    {
        $this->table()
            ->insertOne(['value' => 'example'])
        ;
    }

    #[Test]
    #[ExpectException(ReadonlyConnectionException::class)]
    public function testTableRejectInsertMultiple(): void
    {
        $this->table()
            ->insertMultiple(['value'], ['example'])
        ;
    }

    #[Test]
    #[ExpectException(ReadonlyConnectionException::class)]
    public function testTableRejectInsert(): void
    {
        $this->table()
            ->insert()
            ->columns('value')
            ->values('example')
            ->run();
    }

    #[Test]
    #[ExpectException(ReadonlyConnectionException::class)]
    public function testTableRejectUpdate(): void
    {
        $this->table()
            ->update(['value' => 'updated'])
            ->run()
        ;
    }

    #[Test]
    #[ExpectException(ReadonlyConnectionException::class)]
    public function testTableRejectDelete(): void
    {
        $this->table()
            ->delete()
            ->run()
        ;
    }

    #[Test]
    #[ExpectException(ReadonlyConnectionException::class)]
    public function testTableRejectEraseData(): void
    {
        $this->table()
            ->eraseData()
        ;
    }

    #[Test]
    public function testSchemaRejectSaving(): void
    {
        Expect::exception(ReadonlyConnectionException::class);

        $table = $this->database
            ->table('not_allowed_to_creation');

        $schema = $table->getSchema();
        $schema->primary('id');
        $schema->string('value')->nullable();
        $schema->save();
    }

    #[Test]
    #[ExpectNoAssertions]
    public function testDatabaseAllowSelection(): void
    {
        $this->database->select()
            ->from($this->table)
            ->run()
        ;
    }

    #[Test]
    #[ExpectException(ReadonlyConnectionException::class)]
    public function testDatabaseRejectUpdate(): void
    {
        $this->database->update($this->table, ['value' => 'example'])
            ->run()
        ;
    }

    #[Test]
    #[ExpectException(ReadonlyConnectionException::class)]
    public function testDatabaseRejectInsert(): void
    {
        $this->database->insert($this->table)
            ->columns('value')
            ->values('example')
            ->run()
        ;
    }

    #[Test]
    #[ExpectException(ReadonlyConnectionException::class)]
    public function testDatabaseRejectDelete(): void
    {
        $this->database->delete($this->table)
            ->run()
        ;
    }

    #[Test]
    #[ExpectNoAssertions]
    public function testDatabaseAllowRawQuery(): void
    {
        $this->database->query('SELECT 1');
    }

    #[Test]
    #[ExpectException(ReadonlyConnectionException::class)]
    public function testDatabaseRejectRawExecution(): void
    {
        $this->database->execute("DROP TABLE {$this->table}");
    }

    #[BeforeTest]
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

    #[AfterTest]
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
        /** @var DatabaseDriver $driver */
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
