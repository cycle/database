<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Test;

final class BinaryColumnTest extends DatabaseTestCase
{
    #[Test]
    public function testBit(): void
    {
        $schema = $this->schema('table');

        $column = $schema->bit('column');
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        Assert::same($column->getType(), 'string');
        Assert::same($savedColumn->getType(), 'string');
        Assert::same($column->getAbstractType(), 'bit');
        Assert::same($savedColumn->getAbstractType(), 'bit');
        Assert::same($column->getInternalType(), 'bit');
        Assert::same($savedColumn->getInternalType(), 'bit');
        Assert::same($savedColumn->getSize(), 1);
        Assert::same($column->getSize(), 1);
    }

    #[Test]
    public function testBitWithSize(): void
    {
        $schema = $this->schema('table');

        $column = $schema->bit('column', size: 10);
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        Assert::same($column->getType(), 'string');
        Assert::same($savedColumn->getType(), 'string');
        Assert::same($column->getAbstractType(), 'bit');
        Assert::same($savedColumn->getAbstractType(), 'bit');
        Assert::same($column->getInternalType(), 'bit');
        Assert::same($savedColumn->getInternalType(), 'bit');
        Assert::same($savedColumn->getSize(), 10);
        Assert::same($column->getSize(), 10);
    }

    #[Test]
    public function testBitVarying(): void
    {
        $schema = $this->schema('table');

        $column = $schema->bitVarying('column');
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        Assert::same($column->getType(), 'string');
        Assert::same($savedColumn->getType(), 'string');
        Assert::same($column->getAbstractType(), 'bit');
        Assert::same($savedColumn->getAbstractType(), 'bit');
        Assert::same($column->getInternalType(), 'bit varying');
        Assert::same($savedColumn->getInternalType(), 'bit varying');
        Assert::same($savedColumn->getSize(), 0);
        Assert::same($column->getSize(), 0);
    }

    #[Test]
    public function testBitVaryingWithSize(): void
    {
        $schema = $this->schema('table');

        $column = $schema->bitVarying('column', size: 10);
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        Assert::same($column->getType(), 'string');
        Assert::same($savedColumn->getType(), 'string');
        Assert::same($column->getAbstractType(), 'bit');
        Assert::same($savedColumn->getAbstractType(), 'bit');
        Assert::same($column->getInternalType(), 'bit varying');
        Assert::same($savedColumn->getInternalType(), 'bit varying');
        Assert::same($savedColumn->getSize(), 10);
        Assert::same($column->getSize(), 10);
    }
}
