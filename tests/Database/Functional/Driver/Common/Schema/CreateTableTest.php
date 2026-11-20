<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class CreateTableTest extends BaseTest
{
    #[\Testo\Test]
    public function testEmptyTable(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        \Testo\Assert::same($schema->getPrimaryKeys(), []);
        \Testo\Assert::same($schema->getColumns(), []);
        \Testo\Assert::same($schema->getIndexes(), []);
        \Testo\Assert::same($schema->getForeignKeys(), []);
    }

    #[\Testo\Test]
    public function testSimpleCreation(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        $this->assertSameAsInDB($schema);

        \Testo\Assert::array($schema->__debugInfo());
    }

    #[\Testo\Test]
    public function testMultipleColumns(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->string('name');
        $schema->enum('status', ['active', 'disabled']);
        $schema->float('balance')->defaultValue(0);

        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('status')->getEnumValues(), ['active', 'disabled']);
    }

    #[\Testo\Test]
    public function testCreateAndDrop(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->save();

        \Testo\Assert::same($schema->column('id')->getTable(), 'table');

        \Testo\Assert::true($schema->exists());

        $schema->declareDropped();
        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());
    }

    #[\Testo\Test]
    public function testCreateNoPrimary(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->string('name');
        \Testo\Assert::same($schema->getPrimaryKeys(), []);
        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());

        \Testo\Assert::true($schema->hasColumn('name'));
    }

    #[\Testo\Test]
    public function testCreateWithPrimary(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->integer('id')->nullable(false);
        $schema->string('name');

        $schema->setPrimaryKeys(['id']);
        \Testo\Assert::same($schema->getPrimaryKeys(), ['id']);
        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());

        $this->assertSameAsInDB($schema);
        \Testo\Assert::same($this->fetchSchema($schema)->getPrimaryKeys(), ['id']);
    }

    #[\Testo\Test]
    public function testDeleteNonExisted(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        \Testo\Expect::exception(SchemaException::class);

        $schema->declareDropped();
    }
}
