<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\CreateTableTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class CreateTableTest extends CommonClass
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testCreateAndDrop(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->save();

        \Testo\Assert::same($schema->column('id')->getTable(), 'public.table');

        \Testo\Assert::true($schema->exists());

        $schema->declareDropped();
        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());
    }
}
