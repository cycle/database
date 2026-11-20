<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Connection;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[ExceptDriver(Driver::MySQL)]
class CustomOptionsTest extends DatabaseTestCase
{
    #[Test]
    public function testDecimalSizes(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->decimal('double_2', 10, 1);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
        Assert::same($this->fetchSchema($schema)->column('double_2')->getPrecision(), 10);
        Assert::same($this->fetchSchema($schema)->column('double_2')->getScale(), 1);

        Assert::array($schema->decimal('double_2', 10, 1)->__debugInfo());
    }

    public function sampleSchema(string $table): AbstractTable
    {
        $schema = $this->schema($table);

        if (!$schema->exists()) {
            $schema->primary('id');
            $schema->string('first_name')->nullable(false);
            $schema->string('last_name')->nullable(false);
            $schema->string('email', 64)->nullable(false);
            $schema->enum('status', ['active', 'disabled'])->defaultValue('active');
            $schema->double('balance')->defaultValue(0);
            $schema->boolean('flagged')->defaultValue(true);

            $schema->float('floated')->defaultValue(0);

            $schema->text('bio');

            //Some dates
            $schema->timestamp('timestamp')->defaultValue(AbstractColumn::DATETIME_NOW);
            $schema->datetime('datetime')->defaultValue('2017-01-01 00:00:00');
            $schema->date('datetime')->nullable(true);
            $schema->time('datetime')->defaultValue('00:00');

            $schema->save(Handler::DO_ALL);
        }

        return $schema;
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->database = $this->db(connectionConfig: [
            'options' => [
                /**
                 * Stringify fetches will return everything as string,
                 * so e.g. decimal/numeric type will not be converted to float, thus losing the precision
                 * and letting users handle it differently.
                 *
                 * As a result, int is also returned as string, so we need to make sure
                 * that we're properly casting schema information details.
                 */
                \PDO::ATTR_STRINGIFY_FETCHES => true,
            ],
        ]);
    }
}
