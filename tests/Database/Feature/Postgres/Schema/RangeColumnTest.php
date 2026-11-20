<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Test;

final class RangeColumnTest extends DatabaseTestCase
{
    #[Test]
    public function testInt4range(): void
    {
        $schema = $this->schema('table');

        $column = $schema->int4range('int4range');
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('table')->getColumns()['int4range'];

        Assert::same($column->getType(), 'int');
        Assert::same($savedColumn->getType(), 'int');
        Assert::same($column->getAbstractType(), 'integer');
        Assert::same($savedColumn->getAbstractType(), 'integer');
        Assert::same($column->getInternalType(), 'int4range');
        Assert::same($savedColumn->getInternalType(), 'int4range');
    }

    #[Test]
    public function testInt8range(): void
    {
        $schema = $this->schema('table');

        $column = $schema->int8range('int8range');
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('table')->getColumns()['int8range'];

        Assert::same($column->getType(), 'int');
        Assert::same($savedColumn->getType(), 'int');
        Assert::same($column->getAbstractType(), 'bigInteger');
        Assert::same($savedColumn->getAbstractType(), 'bigInteger');
        Assert::same($column->getInternalType(), 'int8range');
        Assert::same($savedColumn->getInternalType(), 'int8range');
    }

    #[Test]
    public function testNumrange(): void
    {
        $schema = $this->schema('table');

        $column = $schema->numrange('numrange');
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('table')->getColumns()['numrange'];

        Assert::same($column->getType(), 'float');
        Assert::same($savedColumn->getType(), 'float');
        Assert::same($column->getAbstractType(), 'decimal');
        Assert::same($savedColumn->getAbstractType(), 'decimal');
        Assert::same($column->getInternalType(), 'numrange');
        Assert::same($savedColumn->getInternalType(), 'numrange');
    }

    #[Test]
    public function testTsrange(): void
    {
        $schema = $this->schema('table');

        $column = $schema->tsrange('tsrange');
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('table')->getColumns()['tsrange'];

        Assert::false($column->getAttributes()['withTimezone']);
        Assert::false($savedColumn->getAttributes()['withTimezone']);
        Assert::same($column->getType(), 'string');
        Assert::same($savedColumn->getType(), 'string');
        Assert::same($column->getAbstractType(), 'timestamp');
        Assert::same($savedColumn->getAbstractType(), 'timestamp');
        Assert::same($column->getInternalType(), 'tsrange');
        Assert::same($savedColumn->getInternalType(), 'tsrange');
    }

    #[Test]
    public function testTstzrange(): void
    {
        $schema = $this->schema('table');

        $column = $schema->tstzrange('tstzrange');
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('table')->getColumns()['tstzrange'];

        Assert::true($column->getAttributes()['withTimezone']);
        Assert::true($savedColumn->getAttributes()['withTimezone']);
        Assert::same($column->getType(), 'string');
        Assert::same($savedColumn->getType(), 'string');
        Assert::same($column->getAbstractType(), 'timestamptz');
        Assert::same($savedColumn->getAbstractType(), 'timestamptz');
        Assert::same($column->getInternalType(), 'tstzrange');
        Assert::same($savedColumn->getInternalType(), 'tstzrange');
    }

    #[Test]
    public function testDateRange(): void
    {
        $schema = $this->schema('table');

        $column = $schema->daterange('daterange');
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('table')->getColumns()['daterange'];

        Assert::same($column->getType(), 'string');
        Assert::same($savedColumn->getType(), 'string');
        Assert::same($column->getAbstractType(), 'date');
        Assert::same($savedColumn->getAbstractType(), 'date');
        Assert::same($column->getInternalType(), 'daterange');
        Assert::same($savedColumn->getInternalType(), 'daterange');
    }
}
