<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres', 'driver-postgres-array')]
final class ArrayColumnTest extends CommonClass
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('typesDataProvider')]
    public function testArrayType(string $postgres_type, string $internal_type): void
    {
        $driver = $this->database->getDriver();
        $driver->execute("DROP TABLE IF EXISTS array_test");
        $driver->execute("CREATE TABLE array_test ( test $postgres_type )");
        $column = $this->schema('array_test')->column('test');
        \Testo\Assert::same($column->getInternalType(), $internal_type);
    }

    public function typesDataProvider(): \Traversable
    {

        yield ['smallint[]', 'integer[]'];
        yield ['integer[]', 'integer[]'];
        yield ['bigint[]', 'integer[]'];

        yield ['decimal[]', 'float[]'];
        yield ['numeric[]', 'float[]'];
        yield ['real[]', 'float[]'];
        yield ['double precision[]', 'float[]'];

        yield ['character varying[]', 'string[]'];
        yield ['character varying(255)[]', 'string[]'];
        yield ['character(255)[]', 'string[]'];
        yield ['bpchar[]', 'string[]'];
        yield ['text[]', 'string[]'];
    }
}
