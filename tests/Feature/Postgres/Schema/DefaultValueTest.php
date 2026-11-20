<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Database\Driver\Postgres\Exception\PostgresException;
use Cycle\Database\Tests\Feature\Common\Schema\DefaultValueTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[OnlyDriver(Driver::Postgres)]
final class DefaultValueTest extends CommonClass
{
    #[Test]
    public function testJsonDefaultValueEmpty(): void
    {
        Expect::exception(PostgresException::class)->withMessageContaining('Column `public.table.target` of type json/jsonb has an invalid default json value.');
        parent::testJsonDefaultValueEmpty();
    }

    #[Test]
    public function testJsonDefaultValueString(): void
    {
        Expect::exception(PostgresException::class)->withMessageContaining('Column `public.table.target` of type json/jsonb has an invalid default json value.');
        parent::testJsonDefaultValueString();
    }

    #[Test]
    public function testJsonDefaultValueValidJsonString(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->json('target')->defaultValue('{"foo":"bar","baz":100.5}');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }
}
