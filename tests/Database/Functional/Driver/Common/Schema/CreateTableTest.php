<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class CreateTableTest extends BaseTest
{
    public function testEmptyTable(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $this->assertSame([], $schema->getPrimaryKeys());
        $this->assertSame([], $schema->getColumns());
        $this->assertSame([], $schema->getIndexes());
        $this->assertSame([], $schema->getForeignKeys());
    }

    public function testSimpleCreation(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertSameAsInDB($schema);

        $this->assertIsArray($schema->__debugInfo());
    }

    public function testMultipleColumns(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->string('name');
        $schema->enum('status', ['active', 'disabled']);
        $schema->float('balance')->defaultValue(0);

        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertSameAsInDB($schema);

        $this->assertSame(['active', 'disabled'], $schema->column('status')->getEnumValues());
    }

    public function testCreateAndDrop(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->save();

        $this->assertSame('table', $schema->column('id')->getTable());

        $this->assertTrue($schema->exists());

        $schema->declareDropped();
        $schema->save();

        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());
    }

    public function testCreateNoPrimary(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->string('name');
        $this->assertSame([], $schema->getPrimaryKeys());
        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $this->assertTrue($schema->hasColumn('name'));
    }

    public function testCreateWithPrimary(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->integer('id')->nullable(false);
        $schema->string('name');

        $schema->setPrimaryKeys(['id']);
        $this->assertSame(['id'], $schema->getPrimaryKeys());
        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $this->assertSameAsInDB($schema);
        $this->assertSame(['id'], $this->fetchSchema($schema)->getPrimaryKeys());
    }

    public function testDeleteNonExisted(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $this->expectException(SchemaException::class);

        $schema->declareDropped();
    }
}
