<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Postgres\Exception\PostgresException;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\DefaultValueTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class DefaultValueTest extends CommonClass
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testJsonDefaultValueEmpty(): void
    {
        \Testo\Expect::exception(PostgresException::class)->withMessageContaining('Column `public.table.target` of type json/jsonb has an invalid default json value.');
        parent::testJsonDefaultValueEmpty();
    }

    #[\Testo\Test]
    public function testJsonDefaultValueString(): void
    {
        \Testo\Expect::exception(PostgresException::class)->withMessageContaining('Column `public.table.target` of type json/jsonb has an invalid default json value.');
        parent::testJsonDefaultValueString();
    }

    #[\Testo\Test]
    public function testJsonDefaultValueValidJsonString(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->json('target')->defaultValue('{"foo":"bar","baz":100.5}');

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }
}
