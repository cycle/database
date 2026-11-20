<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Exception\DBALException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class PositionColumnTest extends DatabaseTestCase
{
    #[Test]
    public function testPositionFirst(): void
    {
        $schema = $this->sampleSchema('table');

        Assert::true($schema->exists());
        SchemaAssert::sameAsInDb($schema);

        $schema->string('identifier')->nullable(false)->first();
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $updatedSchema      = $this->fetchSchema($schema);
        $updatedColumnNames = \array_map(static fn(AbstractColumn $column) => $column->getName(), $updatedSchema->getColumns());

        $expectedColumnNames = [
            'identifier' => 'identifier',
            'id'         => 'id',
            'first_name' => 'first_name',
            'last_name'  => 'last_name',
            'email'      => 'email',
            'status'     => 'status',
            'balance'    => 'balance',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];

        Assert::same($updatedColumnNames, $expectedColumnNames);
    }

    #[Test]
    public function testPositionAfter(): void
    {
        $schema = $this->sampleSchema('table');

        Assert::true($schema->exists());
        SchemaAssert::sameAsInDb($schema);

        $schema->string('identifier')->nullable(false)->after('email');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $updatedSchema      = $this->fetchSchema($schema);
        $updatedColumnNames = \array_map(static fn(AbstractColumn $column) => $column->getName(), $updatedSchema->getColumns());

        $expectedColumnNames = [
            'id'         => 'id',
            'first_name' => 'first_name',
            'last_name'  => 'last_name',
            'email'      => 'email',
            'identifier' => 'identifier',
            'status'     => 'status',
            'balance'    => 'balance',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];

        Assert::same($updatedColumnNames, $expectedColumnNames);
    }

    #[Test]
    public function testPositionAfterThrowsException(): void
    {
        $schema = $this->sampleSchema('table');

        Assert::true($schema->exists());
        SchemaAssert::sameAsInDb($schema);

        Expect::exception(DBALException::class)->withMessageContaining("Unknown column 'nonexistent'");

        $schema->string('identifier')->nullable(false)->after('nonexistent');
        $schema->save();
    }

    private function sampleSchema(string $table): AbstractTable
    {
        $schema = $this->schema($table);

        if (! $schema->exists()) {
            $schema->primary('id');
            $schema->string('first_name')->nullable(false);
            $schema->string('last_name')->nullable(false);
            $schema->string('email', 64)->nullable(false);
            $schema->enum('status', ['active', 'disabled'])->defaultValue('active');
            $schema->double('balance')->defaultValue(0);
            $schema->datetime('created_at')->defaultValue(AbstractColumn::DATETIME_NOW);
            $schema->datetime('updated_at')->nullable(true);

            $schema->save(Handler::DO_ALL);
        }

        return $schema;
    }
}
