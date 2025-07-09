<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Exception\DBALException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;
use Cycle\Database\Tests\Utils\DontGenerateAttribute;

#[DontGenerateAttribute]
abstract class PositionColumnTest extends BaseTest
{
    public function testPositionFirst(): void
    {
        $schema = $this->sampleSchema('table');

        $this->assertTrue($schema->exists());
        $this->assertSameAsInDB($schema);

        $schema->string('identifier')->nullable(false)->first();
        $schema->save();

        $this->assertSameAsInDB($schema);

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

        $this->assertSame($expectedColumnNames, $updatedColumnNames);
    }

    public function testPositionAfter(): void
    {
        $schema = $this->sampleSchema('table');

        $this->assertTrue($schema->exists());
        $this->assertSameAsInDB($schema);

        $schema->string('identifier')->nullable(false)->after('email');
        $schema->save();

        $this->assertSameAsInDB($schema);

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

        $this->assertSame($expectedColumnNames, $updatedColumnNames);
    }

    public function testPositionAfterThrowsException(): void
    {
        $schema = $this->sampleSchema('table');

        $this->assertTrue($schema->exists());
        $this->assertSameAsInDB($schema);

        $this->expectException(DBALException::class);
        $this->expectExceptionMessage("Unknown column 'nonexistent'");

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
