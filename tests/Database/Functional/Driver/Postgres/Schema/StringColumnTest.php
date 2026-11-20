<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\StringColumnTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
final class StringColumnTest extends CommonClass
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('typesDataProvider')]
    public function testColumnsMappedToString(string $type): void
    {
        $schema = $this->schema('table');

        $column = $schema->{$type}('column');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($savedColumn->getType(), 'string');
        \Testo\Assert::same($column->getAbstractType(), 'string');
        \Testo\Assert::same($savedColumn->getAbstractType(), 'string');
        \Testo\Assert::same($column->getInternalType(), $type);
        \Testo\Assert::same($savedColumn->getInternalType(), $type);
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
