<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
final class RangeColumnTest extends BaseTest
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testInt4range(): void
    {
        $schema = $this->schema('table');

        $column = $schema->int4range('int4range');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['int4range'];

        \Testo\Assert::same($column->getType(), 'int');
        \Testo\Assert::same($savedColumn->getType(), 'int');
        \Testo\Assert::same($column->getAbstractType(), 'integer');
        \Testo\Assert::same($savedColumn->getAbstractType(), 'integer');
        \Testo\Assert::same($column->getInternalType(), 'int4range');
        \Testo\Assert::same($savedColumn->getInternalType(), 'int4range');
    }

    #[\Testo\Test]
    public function testInt8range(): void
    {
        $schema = $this->schema('table');

        $column = $schema->int8range('int8range');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['int8range'];

        \Testo\Assert::same($column->getType(), 'int');
        \Testo\Assert::same($savedColumn->getType(), 'int');
        \Testo\Assert::same($column->getAbstractType(), 'bigInteger');
        \Testo\Assert::same($savedColumn->getAbstractType(), 'bigInteger');
        \Testo\Assert::same($column->getInternalType(), 'int8range');
        \Testo\Assert::same($savedColumn->getInternalType(), 'int8range');
    }

    #[\Testo\Test]
    public function testNumrange(): void
    {
        $schema = $this->schema('table');

        $column = $schema->numrange('numrange');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['numrange'];

        \Testo\Assert::same($column->getType(), 'float');
        \Testo\Assert::same($savedColumn->getType(), 'float');
        \Testo\Assert::same($column->getAbstractType(), 'decimal');
        \Testo\Assert::same($savedColumn->getAbstractType(), 'decimal');
        \Testo\Assert::same($column->getInternalType(), 'numrange');
        \Testo\Assert::same($savedColumn->getInternalType(), 'numrange');
    }

    #[\Testo\Test]
    public function testTsrange(): void
    {
        $schema = $this->schema('table');

        $column = $schema->tsrange('tsrange');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['tsrange'];

        \Testo\Assert::false($column->getAttributes()['withTimezone']);
        \Testo\Assert::false($savedColumn->getAttributes()['withTimezone']);
        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($savedColumn->getType(), 'string');
        \Testo\Assert::same($column->getAbstractType(), 'timestamp');
        \Testo\Assert::same($savedColumn->getAbstractType(), 'timestamp');
        \Testo\Assert::same($column->getInternalType(), 'tsrange');
        \Testo\Assert::same($savedColumn->getInternalType(), 'tsrange');
    }

    #[\Testo\Test]
    public function testTstzrange(): void
    {
        $schema = $this->schema('table');

        $column = $schema->tstzrange('tstzrange');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['tstzrange'];

        \Testo\Assert::true($column->getAttributes()['withTimezone']);
        \Testo\Assert::true($savedColumn->getAttributes()['withTimezone']);
        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($savedColumn->getType(), 'string');
        \Testo\Assert::same($column->getAbstractType(), 'timestamptz');
        \Testo\Assert::same($savedColumn->getAbstractType(), 'timestamptz');
        \Testo\Assert::same($column->getInternalType(), 'tstzrange');
        \Testo\Assert::same($savedColumn->getInternalType(), 'tstzrange');
    }

    #[\Testo\Test]
    public function testDateRange(): void
    {
        $schema = $this->schema('table');

        $column = $schema->daterange('daterange');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['daterange'];

        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($savedColumn->getType(), 'string');
        \Testo\Assert::same($column->getAbstractType(), 'date');
        \Testo\Assert::same($savedColumn->getAbstractType(), 'date');
        \Testo\Assert::same($column->getInternalType(), 'daterange');
        \Testo\Assert::same($savedColumn->getInternalType(), 'daterange');
    }
}
