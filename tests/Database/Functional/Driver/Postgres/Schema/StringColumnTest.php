<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * @group driver
 * @group driver-postgres
 */
final class StringColumnTest extends BaseTest
{
    public const DRIVER = 'postgres';

    /**
     * @dataProvider typesDataProvider
     */
    public function testColumnsMappedToString(string $type): void
    {
        $schema = $this->schema('table');

        $column = $schema->{$type}('column');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('table')->getColumns()['column'];

        $this->assertSame('string', $column->getType());
        $this->assertSame('string', $savedColumn->getType());
        $this->assertSame('string', $column->getAbstractType());
        $this->assertSame('string', $savedColumn->getAbstractType());
        $this->assertSame($type, $column->getInternalType());
        $this->assertSame($type, $savedColumn->getInternalType());
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
