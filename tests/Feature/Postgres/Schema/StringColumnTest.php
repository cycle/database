<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Database\Tests\Feature\Common\Schema\StringColumnTest as CommonClass;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

#[OnlyDriver(Driver::Postgres)]
final class StringColumnTest extends CommonClass
{
    #[Test]
    #[DataProvider('typesDataProvider')]
    public function testColumnsMappedToString(string $type): void
    {
        $schema = $this->schema('table');

        $column = $schema->{$type}('column');
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        Assert::same($column->getType(), 'string');
        Assert::same($savedColumn->getType(), 'string');
        Assert::same($column->getAbstractType(), 'string');
        Assert::same($savedColumn->getAbstractType(), 'string');
        Assert::same($column->getInternalType(), $type);
        Assert::same($savedColumn->getInternalType(), $type);
    }

    public function typesDataProvider(): \Traversable
    {
        yield ['point'];
        yield ['line'];
        yield ['lseg'];
        yield ['box'];
        yield ['path'];
        yield ['polygon'];
        yield ['circle'];
        yield ['cidr'];
        yield ['inet'];
        yield ['macaddr'];
        yield ['macaddr8'];
        yield ['tsvector'];
        yield ['tsquery'];
    }
}
