<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
final class BinaryColumnTest extends BaseTest
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testBit(): void
    {
        $schema = $this->schema('table');

        $column = $schema->bit('column');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($savedColumn->getType(), 'string');
        \Testo\Assert::same($column->getAbstractType(), 'bit');
        \Testo\Assert::same($savedColumn->getAbstractType(), 'bit');
        \Testo\Assert::same($column->getInternalType(), 'bit');
        \Testo\Assert::same($savedColumn->getInternalType(), 'bit');
        \Testo\Assert::same($savedColumn->getSize(), 1);
        \Testo\Assert::same($column->getSize(), 1);
    }

    #[\Testo\Test]
    public function testBitWithSize(): void
    {
        $schema = $this->schema('table');

        $column = $schema->bit('column', size: 10);
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($savedColumn->getType(), 'string');
        \Testo\Assert::same($column->getAbstractType(), 'bit');
        \Testo\Assert::same($savedColumn->getAbstractType(), 'bit');
        \Testo\Assert::same($column->getInternalType(), 'bit');
        \Testo\Assert::same($savedColumn->getInternalType(), 'bit');
        \Testo\Assert::same($savedColumn->getSize(), 10);
        \Testo\Assert::same($column->getSize(), 10);
    }

    #[\Testo\Test]
    public function testBitVarying(): void
    {
        $schema = $this->schema('table');

        $column = $schema->bitVarying('column');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($savedColumn->getType(), 'string');
        \Testo\Assert::same($column->getAbstractType(), 'bit');
        \Testo\Assert::same($savedColumn->getAbstractType(), 'bit');
        \Testo\Assert::same($column->getInternalType(), 'bit varying');
        \Testo\Assert::same($savedColumn->getInternalType(), 'bit varying');
        \Testo\Assert::same($savedColumn->getSize(), 0);
        \Testo\Assert::same($column->getSize(), 0);
    }

    #[\Testo\Test]
    public function testBitVaryingWithSize(): void
    {
        $schema = $this->schema('table');

        $column = $schema->bitVarying('column', size: 10);
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($savedColumn->getType(), 'string');
        \Testo\Assert::same($column->getAbstractType(), 'bit');
        \Testo\Assert::same($savedColumn->getAbstractType(), 'bit');
        \Testo\Assert::same($column->getInternalType(), 'bit varying');
        \Testo\Assert::same($savedColumn->getInternalType(), 'bit varying');
        \Testo\Assert::same($savedColumn->getSize(), 10);
        \Testo\Assert::same($column->getSize(), 10);
    }
}
