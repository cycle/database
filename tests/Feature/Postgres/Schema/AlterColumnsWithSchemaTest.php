<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Database\Database;
use Cycle\Database\Driver\Handler;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\TableInterface;
use Cycle\Database\Tests\Feature\Postgres\Helpers;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class AlterColumnsWithSchemaTest extends DatabaseTestCase
{
    use Helpers;

    /** @var Database */
    private $db;

    #[Test]
    public function testCreatesTableWithSchema(): void
    {
        $schema1 = $this->db->table('test')->getSchema();
        $schema2 = $this->db->table('schema2.test')->getSchema();

        $this->createSchema($schema1);
        Assert::true($schema1->exists());
        Assert::false($schema2->exists());

        $this->createSchema($schema2, true);
        Assert::true($schema2->exists());

        SchemaAssert::sameAsInDb($schema1);
        SchemaAssert::sameAsInDb($schema2);

        Assert::notSame(
            \array_keys($schema2->getColumns()),
            \array_keys($schema1->getColumns()),
            'Tables should be different',
        );
    }

    #[BeforeTest]
    public function connect(): void
    {
        $this->db = new Database('default', '', $this->getDriver(['schema1', 'schema2']));
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
