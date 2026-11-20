<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
final class ArrayColumnTest extends DatabaseTestCase
{
    #[DataProvider('typesDataProvider')]
    public function testArrayType(string $postgres_type, string $internal_type): void
    {
        $driver = $this->database->getDriver();
        $driver->execute("DROP TABLE IF EXISTS array_test");
        $driver->execute("CREATE TABLE array_test ( test $postgres_type )");
        $column = $this->schema('array_test')->column('test');
        Assert::same($column->getInternalType(), $internal_type);
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
