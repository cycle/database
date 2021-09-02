<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\Postgres;

use Cycle\Database\Database;
use Cycle\Database\Driver\Handler;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\TableInterface;
use Cycle\Database\Tests\Traits\Loggable;
use Cycle\Database\Tests\Traits\TableAssertions;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class AlterColumnsWithSchemaTest extends TestCase
{
    use Helpers;
    use TableAssertions;
    use Loggable;

    /** @var Database */
    private $db;

    protected function setUp(): void
    {
        parent::setUp();

        $driver = $this->getDriver(['schema1', 'schema2']);

        $this->db = new Database('default', '', $driver);

        $this->setUpSchemas();
    }

    public function schema(string $table): AbstractTable
    {
        return $this->db->table($table)->getSchema();
    }

    public function testCreatesTableWithSchema(): void
    {
        $schema1 = $this->db->table('test')->getSchema();
        $schema2 = $this->db->table('schema2.test')->getSchema();

        $this->createSchema($schema1);
        $this->assertTrue($schema1->exists());
        $this->assertFalse($schema2->exists());

        $this->createSchema($schema2, true);
        $this->assertTrue($schema2->exists());

        $this->assertSameAsInDB($schema1);
        $this->assertSameAsInDB($schema2);

        try {
            $this->assertSameAsInDB($schema2, $schema1);
            $this->fail('Tables should be different');
        } catch (ExpectationFailedException $e) {
        }
    }

    protected function createSchema(TableInterface $schema, bool $extraFields = false): TableInterface
    {
        $schema->primary('id');
        $schema->string('first_name')->nullable(false);
        $schema->string('last_name')->nullable(false);
        $schema->string('email', 64)->nullable(false);
        $schema->enum('status', ['active', 'disabled'])->defaultValue('active');

        if ($extraFields) {
            $schema->double('balance')->defaultValue(0);
            $schema->boolean('flagged')->defaultValue(true);

            $schema->float('floated')->defaultValue(0);

            $schema->text('about');
            $schema->text('bio');

            $schema->index(['about', 'bio'])->unique(true);
        } else {
            $schema->index(['first_name', 'last_name'])->unique(true);
        }

        //Some dates
        $schema->timestamp('timestamp')->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->datetime('datetime')->defaultValue('2017-01-01 00:00:00');


        $schema->date('datetime')->nullable(true);
        $schema->time('datetime')->defaultValue('00:00');

        $schema->save(Handler::DO_ALL);

        return $schema;
    }
}
