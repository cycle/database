<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Schema;

use Cycle\Database\Exception\SchemaException;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[ExceptDriver(Driver::MySQL, Driver::Postgres)]
class CreateTableTest extends DatabaseTestCase
{
    #[Test]
    public function testEmptyTable(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        Assert::same($schema->getPrimaryKeys(), []);
        Assert::same($schema->getColumns(), []);
        Assert::same($schema->getIndexes(), []);
        Assert::same($schema->getForeignKeys(), []);
    }

    #[Test]
    public function testSimpleCreation(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $schema->primary('id');
        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());
        SchemaAssert::sameAsInDb($schema);

        Assert::array($schema->__debugInfo());
    }

    #[Test]
    public function testMultipleColumns(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $schema->primary('id');
        $schema->string('name');
        $schema->enum('status', ['active', 'disabled']);
        $schema->float('balance')->defaultValue(0);

        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());
        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('status')->getEnumValues(), ['active', 'disabled']);
    }

    #[Test]
    public function testCreateAndDrop(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $schema->primary('id');
        $schema->save();

        Assert::same($schema->column('id')->getTable(), 'table');

        Assert::true($schema->exists());

        $schema->declareDropped();
        $schema->save();

        $schema = $this->schema('table');
        Assert::false($schema->exists());
    }

    #[Test]
    public function testCreateNoPrimary(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $schema->string('name');
        Assert::same($schema->getPrimaryKeys(), []);
        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        Assert::true($schema->hasColumn('name'));
    }

    #[Test]
    public function testCreateWithPrimary(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $schema->integer('id')->nullable(false);
        $schema->string('name');

        $schema->setPrimaryKeys(['id']);
        Assert::same($schema->getPrimaryKeys(), ['id']);
        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        SchemaAssert::sameAsInDb($schema);
        Assert::same($this->fetchSchema($schema)->getPrimaryKeys(), ['id']);
    }

    #[Test]
    public function testDeleteNonExisted(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        Expect::exception(SchemaException::class);

        $schema->declareDropped();
    }
}
