<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * @group driver
 * @group driver-postgres
 */
final class BitColumnTest extends BaseTest
{
    public const DRIVER = 'postgres';

    public function testBit(): void
    {
        $schema = $this->schema('table');

        $column = $schema->bit('column');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        $this->assertSame('string', $column->getType());
        $this->assertSame('string', $savedColumn->getType());
        $this->assertSame('bit', $column->getAbstractType());
        $this->assertSame('bit', $savedColumn->getAbstractType());
        $this->assertSame('bit', $column->getInternalType());
        $this->assertSame('bit', $savedColumn->getInternalType());
        $this->assertSame(1, $savedColumn->getSize());
        $this->assertSame(1, $column->getSize());
    }

    public function testBitWithSize(): void
    {
        $schema = $this->schema('table');

        $column = $schema->bit('column', size: 10);
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        $this->assertSame('string', $column->getType());
        $this->assertSame('string', $savedColumn->getType());
        $this->assertSame('bit', $column->getAbstractType());
        $this->assertSame('bit', $savedColumn->getAbstractType());
        $this->assertSame('bit', $column->getInternalType());
        $this->assertSame('bit', $savedColumn->getInternalType());
        $this->assertSame(10, $savedColumn->getSize());
        $this->assertSame(10, $column->getSize());
    }

    public function testBitVarying(): void
    {
        $schema = $this->schema('table');

        $column = $schema->bitVarying('column');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        $this->assertSame('string', $column->getType());
        $this->assertSame('string', $savedColumn->getType());
        $this->assertSame('bit', $column->getAbstractType());
        $this->assertSame('bit', $savedColumn->getAbstractType());
        $this->assertSame('bit varying', $column->getInternalType());
        $this->assertSame('bit varying', $savedColumn->getInternalType());
        $this->assertSame(0, $savedColumn->getSize());
        $this->assertSame(0, $column->getSize());
    }

    public function testBitVaryingWithSize(): void
    {
        $schema = $this->schema('table');

        $column = $schema->bitVarying('column', size: 10);
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        $this->assertSame('string', $column->getType());
        $this->assertSame('string', $savedColumn->getType());
        $this->assertSame('bit', $column->getAbstractType());
        $this->assertSame('bit', $savedColumn->getAbstractType());
        $this->assertSame('bit varying', $column->getInternalType());
        $this->assertSame('bit varying', $savedColumn->getInternalType());
        $this->assertSame(10, $savedColumn->getSize());
        $this->assertSame(10, $column->getSize());
    }
}
