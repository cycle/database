<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * @group driver
 * @group driver-postgres
 */
final class RangeColumnTest extends BaseTest
{
    public const DRIVER = 'postgres';

    public function testInt4range(): void
    {
        $schema = $this->schema('table');

        $column = $schema->int4range('int4range');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['int4range'];

        $this->assertSame('int', $column->getType());
        $this->assertSame('int', $savedColumn->getType());
        $this->assertSame('integer', $column->getAbstractType());
        $this->assertSame('integer', $savedColumn->getAbstractType());
        $this->assertSame('int4range', $column->getInternalType());
        $this->assertSame('int4range', $savedColumn->getInternalType());
    }

    public function testInt8range(): void
    {
        $schema = $this->schema('table');

        $column = $schema->int8range('int8range');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['int8range'];

        $this->assertSame('int', $column->getType());
        $this->assertSame('int', $savedColumn->getType());
        $this->assertSame('bigInteger', $column->getAbstractType());
        $this->assertSame('bigInteger', $savedColumn->getAbstractType());
        $this->assertSame('int8range', $column->getInternalType());
        $this->assertSame('int8range', $savedColumn->getInternalType());
    }

    public function testNumrange(): void
    {
        $schema = $this->schema('table');

        $column = $schema->numrange('numrange');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['numrange'];

        $this->assertSame('float', $column->getType());
        $this->assertSame('float', $savedColumn->getType());
        $this->assertSame('decimal', $column->getAbstractType());
        $this->assertSame('decimal', $savedColumn->getAbstractType());
        $this->assertSame('numrange', $column->getInternalType());
        $this->assertSame('numrange', $savedColumn->getInternalType());
    }

    public function testTsrange(): void
    {
        $schema = $this->schema('table');

        $column = $schema->tsrange('tsrange');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['tsrange'];

        $this->assertFalse($column->getAttributes()['withTimezone']);
        $this->assertFalse($savedColumn->getAttributes()['withTimezone']);
        $this->assertSame('string', $column->getType());
        $this->assertSame('string', $savedColumn->getType());
        $this->assertSame('timestamp', $column->getAbstractType());
        $this->assertSame('timestamp', $savedColumn->getAbstractType());
        $this->assertSame('tsrange', $column->getInternalType());
        $this->assertSame('tsrange', $savedColumn->getInternalType());
    }

    public function testTstzrange(): void
    {
        $schema = $this->schema('table');

        $column = $schema->tstzrange('tstzrange');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['tstzrange'];

        $this->assertTrue($column->getAttributes()['withTimezone']);
        $this->assertTrue($savedColumn->getAttributes()['withTimezone']);
        $this->assertSame('string', $column->getType());
        $this->assertSame('string', $savedColumn->getType());
        $this->assertSame('timestamptz', $column->getAbstractType());
        $this->assertSame('timestamptz', $savedColumn->getAbstractType());
        $this->assertSame('tstzrange', $column->getInternalType());
        $this->assertSame('tstzrange', $savedColumn->getInternalType());
    }

    public function testDateRange(): void
    {
        $schema = $this->schema('table');

        $column = $schema->daterange('daterange');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['daterange'];

        $this->assertSame('string', $column->getType());
        $this->assertSame('string', $savedColumn->getType());
        $this->assertSame('date', $column->getAbstractType());
        $this->assertSame('date', $savedColumn->getAbstractType());
        $this->assertSame('daterange', $column->getInternalType());
        $this->assertSame('daterange', $savedColumn->getInternalType());
    }
}
